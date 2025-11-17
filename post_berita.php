<?php
// post_berita.php
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

// Ambil data berita
$berita = query("
    SELECT b.*, e.nama_ekskul, e.id as eskul_id, u.name as penulis
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.is_published = 1
", [$id], 'i');

if (!$berita || $berita->num_rows == 0) {
    setFlash('danger', 'Berita tidak ditemukan!');
    redirect('update_kegiatan.php');
}

$data = $berita->fetch_assoc();

// Update views
execute("UPDATE berita SET views = views + 1 WHERE id = ?", [$id], 'i');

$page_title = $data['judul'];
require_once 'includes/header.php';

// Berita lainnya dari eskul yang sama
$related = query("
    SELECT * FROM berita 
    WHERE ekstrakurikuler_id = ? AND id != ? AND is_published = 1
    ORDER BY created_at DESC 
    LIMIT 3
", [$data['eskul_id'], $id], 'ii');
?>

<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 text-white">
  <div class="max-w-7xl mx-auto px-6 py-8">
    <nav class="text-sm text-emerald-100/80 mb-5">
      <a href="<?php echo BASE_URL; ?>" class="hover:text-white">Beranda</a>
      <span class="mx-2">/</span>
      <a href="<?php echo BASE_URL; ?>update_kegiatan.php" class="hover:text-white">Berita</a>
      <span class="mx-2">/</span>
      <span class="text-white font-semibold"><?php echo substr($data['judul'], 0, 30); ?>...</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
      <!-- Main -->
      <div class="lg:col-span-2">
        <article class="bg-white text-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/10 overflow-hidden">
          <?php if ($data['gambar']): ?>
          <img src="<?php echo UPLOAD_URL . $data['gambar']; ?>" alt="<?php echo $data['judul']; ?>" class="w-full h-64 md:h-[28rem] object-cover" />
          <?php endif; ?>
          <div class="p-6 md:p-8">
            <!-- Badge Eskul -->
            <div class="mb-3">
              <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $data['eskul_id']; ?>" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-semibold">
                <i class="bi bi-grid"></i> <?php echo $data['nama_ekskul']; ?>
              </a>
            </div>

            <!-- Title -->
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 mb-3"><?php echo $data['judul']; ?></h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-slate-500 mb-4 pb-4 border-b border-slate-200">
              <div><i class="bi bi-person-circle"></i> <?php echo $data['penulis'] ?? 'Admin'; ?></div>
              <div><i class="bi bi-calendar"></i> <?php echo formatTanggal($data['tanggal_post']); ?></div>
              <div><i class="bi bi-eye"></i> <?php echo $data['views']; ?> views</div>
            </div>

            <!-- Content -->
            <div class="text-slate-700 leading-8 text-[1.05rem] space-y-4">
              <?php echo nl2br(htmlspecialchars($data['konten'])); ?>
            </div>

            <!-- Share Buttons -->
            <div class="my-6 h-px bg-slate-200"></div>
            <div class="flex flex-wrap items-center gap-2">
              <strong class="mr-2">Bagikan:</strong>
              <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL . 'post_berita.php?id=' . $id); ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700"><i class="bi bi-facebook"></i> Facebook</a>
              <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL . 'post_berita.php?id=' . $id); ?>&text=<?php echo urlencode($data['judul']); ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-sky-500 text-white text-sm font-semibold hover:bg-sky-600"><i class="bi bi-twitter"></i> Twitter</a>
              <a href="https://wa.me/?text=<?php echo urlencode($data['judul'] . ' - ' . BASE_URL . 'post_berita.php?id=' . $id); ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            </div>
          </div>
        </article>

        <!-- Berita Terkait -->
        <?php if ($related && $related->num_rows > 0): ?>
        <div class="mt-6 bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200">
            <h5 class="font-bold text-slate-900"><i class="bi bi-newspaper"></i> Berita Lainnya dari <?php echo $data['nama_ekskul']; ?></h5>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php while ($rel = $related->fetch_assoc()): ?>
              <div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-hidden h-full">
                <?php if ($rel['gambar']): ?>
                <img src="<?php echo UPLOAD_URL . $rel['gambar']; ?>" alt="<?php echo $rel['judul']; ?>" class="h-36 w-full object-cover" />
                <?php endif; ?>
                <div class="p-4">
                  <h6 class="font-semibold text-slate-800 mb-1">
                    <a href="?id=<?php echo $rel['id']; ?>" class="hover:text-emerald-700">
                      <?php echo substr($rel['judul'], 0, 50); ?>...
                    </a>
                  </h6>
                  <div class="text-slate-500 text-xs"><i class="bi bi-calendar"></i> <?php echo formatTanggal($rel['tanggal_post']); ?></div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <aside class="space-y-4">
        <!-- Info Eskul -->
        <div class="bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
          <div class="px-6 py-4 bg-emerald-600 text-white">
            <h6 class="font-semibold"><i class="bi bi-info-circle"></i> Tentang Eskul Ini</h6>
          </div>
          <div class="p-6">
            <h5 class="font-bold text-slate-900 mb-3"><?php echo $data['nama_ekskul']; ?></h5>
            <div class="grid gap-2">
              <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $data['eskul_id']; ?>" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">
                <i class="bi bi-eye"></i> Lihat Profil
              </a>
              <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-emerald-600 text-emerald-700 font-semibold hover:bg-emerald-50">
                <i class="bi bi-pencil-square"></i> Daftar Sekarang
              </a>
            </div>
          </div>
        </div>

        <!-- Berita Terpopuler -->
        <div class="bg-white rounded-2xl shadow ring-1 ring-slate-900/10 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200">
            <h6 class="font-semibold text-slate-800"><i class="bi bi-fire"></i> Berita Terpopuler</h6>
          </div>
          <div class="p-6">
            <?php
            $populer = query("
                SELECT b.id, b.judul, b.views, b.tanggal_post
                FROM berita b
                WHERE b.is_published = 1
                ORDER BY b.views DESC
                LIMIT 5
            ");
            $no = 1;
            while ($pop = $populer->fetch_assoc()):
            ?>
            <div class="mb-3 pb-3 border-b border-slate-200">
              <div class="flex items-start gap-2">
                <div class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded bg-emerald-600 text-white text-xs font-bold px-2"><?php echo $no++; ?></div>
                <div class="flex-1">
                  <a href="?id=<?php echo $pop['id']; ?>" class="font-semibold text-slate-800 hover:text-emerald-700 block">
                    <?php echo substr($pop['judul'], 0, 60); ?>...
                  </a>
                  <div class="text-slate-500 text-xs mt-1"><i class="bi bi-eye"></i> <?php echo $pop['views']; ?> views</div>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
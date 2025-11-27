<?php
// includes/footer.php
?>
<!-- Footer (Tailwind) -->
<footer class=" text-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-900">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div>
                <h5 class="text-white font-extrabold mb-3 flex items-center gap-2">
                    <i class="bi bi-mortarboard-fill"></i>
                    MTsN 1 Lebak
                </h5>
                <p class="text-slate-300">Sistem Informasi Manajemen Ekstrakurikuler untuk memudahkan pengelolaan dan monitoring kegiatan ekstrakurikuler sekolah.</p>
                <div class="mt-4 flex items-center gap-4 text-white/80">
                    <a href="#" class="hover:text-white"><i class="bi bi-facebook text-xl"></i></a>
                    <a href="#" class="hover:text-white"><i class="bi bi-instagram text-xl"></i></a>
                    <a href="#" class="hover:text-white"><i class="bi bi-youtube text-xl"></i></a>
                    <a href="#" class="hover:text-white"><i class="bi bi-envelope text-xl"></i></a>
                </div>
            </div>
            <div>
                <h5 class="text-white font-extrabold mb-3">Quick Links</h5>
                <ul class="space-y-2">
                    <li><a href="<?php echo BASE_URL; ?>" class="hover:text-white inline-flex items-center gap-2"><i class="bi bi-chevron-right"></i> Beranda</a></li>
                    <li><a href="<?php echo BASE_URL; ?>profile_eskul.php" class="hover:text-white inline-flex items-center gap-2"><i class="bi bi-chevron-right"></i> Ekstrakurikuler</a></li>
                    <li><a href="<?php echo BASE_URL; ?>update_kegiatan.php" class="hover:text-white inline-flex items-center gap-2"><i class="bi bi-chevron-right"></i> Berita & Kegiatan</a></li>
                    <li><a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="hover:text-white inline-flex items-center gap-2"><i class="bi bi-chevron-right"></i> Pendaftaran</a></li>
                    <li><a href="<?php echo BASE_URL; ?>cetak_sertifikat.php" class="hover:text-white inline-flex items-center gap-2"><i class="bi bi-chevron-right"></i> Cetak Sertifikat</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-white font-extrabold mb-3">Kontak</h5>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2"><i class="bi bi-geo-alt-fill"></i><span>Jl. Raya Rangkasbitung, Lebak, Banten</span></li>
                    <li class="flex items-start gap-2"><i class="bi bi-telephone-fill"></i><span>(0252) 123 ----</span></li>
                    <li class="flex items-start gap-2"><i class="bi bi-envelope-fill"></i><span>demo@mtsn1lebak.sch.id</span></li>
                    <li class="flex items-start gap-2"><i class="bi bi-clock-fill"></i><span>Senin - Jumat: 07:00 - 15:00</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col md:flex-row items-center justify-between text-sm text-slate-300">
            <div>&copy; <?php echo date('Y'); ?> MTsN 1 Lebak. All Rights Reserved.</div>
            <div>Developed with <i class="bi bi-heart-fill text-red-500"></i> by Tim IT Universitas Pamulang</div>
        </div>
    </div>
</footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>

</body>
</html>
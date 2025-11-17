<?php
// daftar_eskul.php
$page_title = 'Pendaftaran Ekstrakurikuler';
require_once 'config/database.php';
// Ambil daftar eskul aktif
$eskul_list = query("SELECT * FROM ekstrakurikulers WHERE status = 'aktif' ORDER BY nama_ekskul");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nis = $_POST['nis'];
    $name = $_POST['name'];
    $kelas = $_POST['kelas'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'] ?? '';
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'];
    $alasan_daftar = $_POST['alasan_daftar'];
    
    // Cek apakah NIS sudah ada
    $cek_user = query("SELECT * FROM users WHERE nis = ?", [$nis], 's');
    
    if ($cek_user && $cek_user->num_rows > 0) {
        $user = $cek_user->fetch_assoc();
        $user_id = $user['id'];
        
        // Update data user (tanpa password)
        execute("UPDATE users SET name = ?, kelas = ?, jenis_kelamin = ?, no_hp = ?, alamat = ?, email = ? WHERE id = ?",
            [$name, $kelas, $jenis_kelamin, $no_hp, $alamat, $email, $user_id], 'ssssssi');
    } else {
        // Insert user baru dengan password default
        $default_password = password_hash('password123', PASSWORD_DEFAULT); // Password default
        
        $result = execute(
            "INSERT INTO users (nis, name, email, password, kelas, jenis_kelamin, no_hp, alamat, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$nis, $name, $email, $default_password, $kelas, $jenis_kelamin, $no_hp, $alamat, 'siswa'],
            'sssssssss'
        );

        
        $user_id = $result['insert_id'];
    }
    
    // Cek apakah sudah pernah daftar eskul yang sama
    $cek_pendaftaran = query("SELECT * FROM anggota_ekskul WHERE user_id = ? AND ekstrakurikuler_id = ?", [$user_id, $ekstrakurikuler_id], 'ii');
    
    if ($cek_pendaftaran && $cek_pendaftaran->num_rows > 0) {
        setFlash('warning', 'Anda sudah pernah mendaftar ekstrakurikuler ini!');
    } else {
        // Insert pendaftaran
        $tanggal_daftar = date('Y-m-d');
        $result = execute("INSERT INTO anggota_ekskul (user_id, ekstrakurikuler_id, tanggal_daftar, alasan_daftar, status) VALUES (?, ?, ?, ?, 'pending')",
            [$user_id, $ekstrakurikuler_id, $tanggal_daftar, $alasan_daftar], 'iiss');
        
        if ($result['success']) {
            setFlash('success', 'Pendaftaran berhasil! Silakan tunggu konfirmasi dari admin. Password default Anda: <strong>password123</strong>');
        } else {
            setFlash('danger', 'Pendaftaran gagal! Silakan coba lagi.');
        }
    }
    
    redirect('daftar_eskul.php');
}
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-success text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Formulir Pendaftaran Ekstrakurikuler
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Silakan isi formulir dengan lengkap dan benar. 
                        Pendaftaran akan diproses oleh admin.
                    </div>

                    <form method="POST" action="">
                        <h5 class="mb-3 text-success">
                            <i class="bi bi-person-fill"></i> Data Siswa
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NIS <span class="text-danger">*</span></label>
                                <input type="text" name="nis" class="form-control" required>
                                <small class="text-muted">Nomor Induk Siswa</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas" class="form-select" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php 
                                    for ($i = 7; $i <= 9; $i++) {
                                        foreach (['A', 'B', 'C', 'D'] as $huruf) {
                                            echo "<option value='$i$huruf'>$i$huruf</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="">Pilih</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No HP/WA <span class="text-danger">*</span></label>
                                <input type="text" name="no_hp" class="form-control" placeholder="081234567890" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com">
                                <small class="text-muted">Opsional</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                <textarea name="alamat" class="form-control" rows="2" placeholder="Masukkan alamat lengkap" required></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 text-success">
                            <i class="bi bi-grid-fill"></i> Pilihan Ekstrakurikuler
                        </h5>

                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" class="form-select" id="eskulSelect" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>" 
                                    data-deskripsi="<?php echo htmlspecialchars($eskul['deskripsi']); ?>">
                                    <?php echo $eskul['nama_ekskul']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="eskulInfo" class="alert alert-light d-none">
                            <strong><i class="bi bi-info-circle"></i> Tentang Eskul Ini:</strong>
                            <p id="infoDeskripsi" class="mb-0 mt-2">-</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alasan Mendaftar <span class="text-danger">*</span></label>
                            <textarea name="alasan_daftar" class="form-control" rows="4" 
                                placeholder="Ceritakan alasan Anda ingin bergabung dengan ekstrakurikuler ini..." required></textarea>
                            <small class="text-muted">Minimal 20 karakter</small>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agree" required>
                            <label class="form-check-label" for="agree">
                                Saya menyatakan bahwa data yang saya isi adalah benar dan siap mengikuti kegiatan ekstrakurikuler dengan penuh tanggung jawab.
                            </label>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-key-fill"></i> <strong>Password Default:</strong> Setelah mendaftar, Anda akan mendapatkan akun dengan password: <strong>password123</strong>
                            <br><small>Silakan ganti password setelah login pertama kali.</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-send"></i> Kirim Pendaftaran
                            </button>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-question-circle"></i> Informasi Penting</h6>
                    <ul class="mb-0">
                        <li>Pastikan data yang Anda isi sudah benar</li>
                        <li>Pendaftaran akan diverifikasi oleh admin (1-3 hari kerja)</li>
                        <li>Anda akan dihubungi melalui nomor HP/WA yang terdaftar</li>
                        <li>Setiap siswa dapat mendaftar maksimal 2 ekstrakurikuler</li>
                        <li>Kegiatan ekstrakurikuler wajib diikuti sesuai jadwal</li>
                        <li>Password default untuk login: <strong>password123</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show eskul info when selected
document.getElementById('eskulSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const info = document.getElementById('eskulInfo');
    
    if (this.value) {
        document.getElementById('infoDeskripsi').textContent = option.dataset.deskripsi || 'Tidak ada deskripsi';
        info.classList.remove('d-none');
    } else {
        info.classList.add('d-none');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const alasan = document.querySelector('[name="alasan_daftar"]').value;
    if (alasan.length < 20) {
        e.preventDefault();
        alert('Alasan mendaftar minimal 20 karakter!');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
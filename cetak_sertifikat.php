<?php
// cetak_sertifikat.php (Fixed Layout - Tidak Tumpang Tindih)
require_once 'config/database.php';
require_once 'config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Cetak Sertifikat';

$print_mode = false;
$sertifikat = null;

// Cek sertifikat berdasarkan NIS atau ID
if (isset($_POST['nisn']) || isset($_GET['nisn']) || isset($_GET['id'])) {
    $nisn = $_POST['nisn'] ?? $_GET['nisn'] ?? null;
    $anggota_id = $_GET['id'] ?? null;
    
    if ($anggota_id) {
        $result = query("
            SELECT u.nisn, u.name, u.kelas, e.nama_ekskul, pembina.name as nama_pembina, 
                   sert.nomor_sertifikat, sert.tanggal_terbit, sert.keterangan,
                   ae.nilai, ae.tanggal_penilaian, ae.catatan_pembina, ae.tanggal_daftar
            FROM anggota_ekskul ae
            JOIN users u ON ae.user_id = u.id
            JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
            LEFT JOIN users pembina ON e.pembina_id = pembina.id
            LEFT JOIN sertifikats sert ON ae.id = sert.anggota_id
            WHERE ae.id = ? AND ae.status = 'diterima'
            LIMIT 1
        ", [$anggota_id], 'i');
    } else {
        $result = query("
            SELECT u.nisn, u.name, u.kelas, e.nama_ekskul, pembina.name as nama_pembina, 
                   sert.nomor_sertifikat, sert.tanggal_terbit, sert.keterangan,
                   ae.nilai, ae.tanggal_penilaian, ae.catatan_pembina, ae.tanggal_daftar, ae.id as anggota_id
            FROM users u
            JOIN anggota_ekskul ae ON u.id = ae.user_id
            JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
            LEFT JOIN users pembina ON e.pembina_id = pembina.id
            LEFT JOIN sertifikats sert ON ae.id = sert.anggota_id
            WHERE u.nisn = ? AND ae.status = 'diterima' AND u.role = 'siswa'
            ORDER BY sert.tanggal_terbit DESC
            LIMIT 1
        ", [$nisn], 's');
    }
    
    if ($result && $result->num_rows > 0) {
        $sertifikat = $result->fetch_assoc();
        
        if (!$sertifikat['nomor_sertifikat']) {
            $nomor = 'CERT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $tanggal_terbit = date('Y-m-d');
            $aid = $sertifikat['anggota_id'] ?? $anggota_id;
            
            query("INSERT INTO sertifikats (anggota_id, nomor_sertifikat, tanggal_terbit) VALUES (?, ?, ?)",
                [$aid, $nomor, $tanggal_terbit], 'iss');
            
            $sertifikat['nomor_sertifikat'] = $nomor;
            $sertifikat['tanggal_terbit'] = $tanggal_terbit;
        }
        
        if (isset($_GET['print'])) {
            $print_mode = true;
        }
    }
}

//  Hanya load header jika BUKAN mode print
if (!$print_mode) {
    require_once 'includes/header.php';
}

// Predikat sekolah (bisa diubah manual di sini)
$predikat_sekolah = 'TERAKREDITASI A';

// Fungsi untuk konversi nilai ke predikat
function getNilaiPredikat($nilai) {
    switch($nilai) {
        case 'A': return 'SANGAT BAIK';
        case 'B': return 'BAIK';
        case 'C': return 'CUKUP';
        default: return '-';
    }
}

// Tahun Kurikulum
$tahun_kurikulum = date('Y') . '/' . (date('Y') + 1);
?>

<?php if (!$print_mode): ?>
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-900 text-white">
    <div class="max-w-3xl mx-auto px-6 py-16">
        <div class="reveal bg-white text-slate-800 rounded-2xl shadow-xl ring-1 ring-slate-900/10 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-700 to-emerald-600 text-white px-6 py-5 text-center">
                <h3 class="text-2xl font-extrabold flex items-center justify-center gap-2">
                    <i class="bi bi-award-fill"></i>
                    Cetak Sertifikat
                </h3>
            </div>
            <div class="p-6">
                <div class="mb-4 rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 px-4 py-3 flex items-start gap-2">
                    <i class="bi bi-info-circle mt-0.5"></i>
                    <span>Masukkan NISN Anda untuk mengecek dan mencetak sertifikat.</span>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">NISN (Nomor Induk Siswa Nasional)</label>
                        <input type="text" name="nisn" placeholder="Masukkan NISN Anda" required autofocus
                               class="mt-1 w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 px-4 py-3" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow">
                            <i class="bi bi-search"></i>
                            Cek Sertifikat
                        </button>
                        <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50">
                            <i class="bi bi-arrow-left"></i>
                            Kembali
                        </a>
                    </div>
                </form>

                <?php if (isset($_POST['nisn']) && !$sertifikat): ?>
                <div class="mt-5 rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-200 px-4 py-3 flex items-start gap-2">
                    <i class="bi bi-exclamation-triangle mt-0.5"></i>
                    <span>Sertifikat tidak ditemukan. Pastikan Anda sudah terdaftar dan aktif di ekstrakurikuler.</span>
                </div>
                <?php endif; ?>

                <?php if ($sertifikat && !$print_mode): ?>
                <div class="my-6 h-px bg-slate-200"></div>
                <div class="rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-5">
                    <h5 class="font-bold text-emerald-800 flex items-center gap-2"><i class="bi bi-check-circle"></i> Sertifikat Ditemukan!</h5>
                    <div class="my-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div class="flex justify-between sm:block"><span class="text-slate-500">Nama</span><span class="font-semibold text-slate-800"><?php echo $sertifikat['name']; ?></span></div>
                        <div class="flex justify-between sm:block"><span class="text-slate-500">NISN</span><span class="font-semibold text-slate-800"><?php echo $sertifikat['nisn']; ?></span></div>
                        <div class="flex justify-between sm:block"><span class="text-slate-500">Kelas</span><span class="font-semibold text-slate-800"><?php echo $sertifikat['kelas']; ?></span></div>
                        <div class="flex justify-between sm:block"><span class="text-slate-500">Ekstrakurikuler</span><span class="font-semibold text-slate-800"><?php echo $sertifikat['nama_ekskul']; ?></span></div>
                        <?php if ($sertifikat['nilai']): ?>
                        <div class="sm:col-span-2 flex items-center gap-2">
                            <span class="text-slate-500">Nilai</span>
                            <?php 
                              $badge = $sertifikat['nilai'] == 'A' ? 'bg-emerald-600 text-white' : ($sertifikat['nilai'] == 'B' ? 'bg-amber-400 text-black' : 'bg-rose-500 text-white');
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge; ?>">
                                Nilai <?php echo $sertifikat['nilai']; ?> - <?php echo getNilaiPredikat($sertifikat['nilai']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between sm:block"><span class="text-slate-500">Nomor Sertifikat</span><span class="font-semibold text-slate-800"><?php echo $sertifikat['nomor_sertifikat']; ?></span></div>
                        <div class="flex justify-between sm:block"><span class="text-slate-500">Tanggal Terbit</span><span class="font-semibold text-slate-800"><?php echo formatTanggal($sertifikat['tanggal_terbit']); ?></span></div>
                    </div>
                    <div class="mt-3">
                        <a href="?nisn=<?php echo $sertifikat['nisn']; ?>&print=1" target="_blank" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 shadow">
                            <i class="bi bi-printer"></i>
                            Cetak Sertifikat
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php 
// Hanya load footer jika BUKAN mode print
require_once 'includes/footer.php'; 
?>

<?php else: ?>
<!-- CERTIFICATE DESIGN - FULL PAGE WITHOUT NAVBAR -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat - <?php echo $sertifikat['name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: #e8ecef;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            display: flex;
            gap: 10px;
        }
        
        .no-print button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Montserrat', sans-serif;
        }
        
        .btn-print {
            background: #198754;
            color: white;
        }
        
        .btn-print:hover {
            background: #146c43;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 135, 84, 0.4);
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
        }
        
        .btn-close:hover {
            background: #5c636a;
        }
        
        /* Certificate Container */
        .certificate {
            width: 297mm;
            height: 210mm;
            position: relative;
            background-image: url('<?php echo BASE_URL; ?>assets/img/certificate-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 0 50px rgba(0,0,0,0.2);
        }
        
        /* Tahun Kurikulum - KANAN ATAS */
        .tahun-kurikulum {
            position: absolute;
            top: 15mm;
            right: 20mm;
            background: #003366;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        /* School Header */
        .school-header {
            position: absolute;
            top: 25mm;
            left: 0;
            right: 0;
            text-align: center;
            z-index: 5;
        }
        
        .school-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 34px;
            font-weight: 700;
            color: #003366;
            margin: 0 0 8px 0;
            letter-spacing: 4px;
        }
        
        .school-header .predikat-badge {
            display: inline-block;
            background: #003366;
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 2px;
        }
        
        .school-header p {
            font-size: 13px;
            color: #555;
            margin: 0;
        }
        
        /* Main Certificate Content */
        .cert-main {
            position: absolute;
            top: 65mm;
            left: 30mm;
            right: 30mm;
            text-align: center;
        }
        
        .cert-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 52px;
            font-weight: 700;
            color: #003366;
            letter-spacing: 10px;
            margin-bottom: 8px;
        }
        
        .cert-subtitle {
            font-size: 15px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }
        
        .cert-text {
            font-size: 13px;
            color: #444;
            margin: 12px 0;
            line-height: 1.5;
        }
        
        .student-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 40px;
            font-weight: 700;
            color: #003366;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .student-details {
            font-size: 13px;
            color: #555;
            margin: 10px 0;
        }
        
        .student-details span {
            display: inline-block;
            margin: 0 12px;
            padding: 4px 14px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .eskul-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 700;
            color: #003366;
            margin: 18px 0;
            text-transform: uppercase;
        }
        
        /* Grade Section */
        .grade-section {
            margin: 18px 0 0 0;
        }
        
        .nilai-display {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 3px;
            margin: 8px 0;
            border: 3px solid #003366;
        }
        
        .nilai-A {
            background: #28a745 !important;
            color: white !important;
        }
        
        .nilai-B {
            background: skyblue !important;
            color: #000 !important;
        }
        
        .nilai-C {
            background: #dc3545 !important;
            color: white !important;
        }
        
        .predikat {
            font-size: 14px;
            font-weight: 700;
            color: #003366;
            margin-top: 6px;
        }
        
        .catatan-pembina {
            font-size: 11px;
            color: #666;
            font-style: italic;
            margin-top: 10px;
            padding: 8px 18px;
            border-radius: 6px;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Signature Area - KIRI BAWAH */
        .signature-area {
            position: absolute;
            bottom: 25mm;
            left: 40mm;
            width: 200px;
            height: auto;
        }
        
        .signature-block {
            width: 100%;
            text-align: center;
        }
        
        .sig-location {
            font-size: 11px;
            color: #555;
            margin: 0 0 5px 0;
            font-weight: 600;
        }
        
        .sig-title {
            font-size: 12px;
            font-weight: 700;
            color: #003366;
            margin: 0 0 6px 0;
            text-transform: uppercase;
        }
        
        .sig-image {
            width: 160px;
            height: 65px;
            margin: 6px auto;
            display: block;
            object-fit: contain;
        }
        
        .sig-name {
            font-size: 14px;
            font-weight: 700;
            color: #003366;
            border-top: 2px solid #003366;
            padding-top: 6px;
            margin: 4px 0 0 0;
            text-transform: uppercase;
        }
        
        .sig-nip {
            font-size: 10px;
            color: #777;
            margin: 3px 0 0 0;
        }
        
        /* Certificate Number - TENGAH BAWAH */
        .cert-number {
            position: absolute;
            bottom: 18mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #888;
            font-weight: 600;
        }
        
        .cert-number strong {
            color: #003366;
            font-weight: 700;
        }
        
        @media print {
            body { 
                background: white; 
            }
            .no-print { 
                display: none !important; 
            }
            .certificate { 
                box-shadow: none;
                page-break-inside: avoid;
            }
            
            /* Force colors in print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            .nilai-A {
                background: #28a745 !important;
                color: white !important;
            }
            
            .nilai-B {
                background: skyblue !important;
                color: #000 !important;
            }
            
            .nilai-C {
                background: #dc3545 !important;
                color: white !important;
            }
            
            .tahun-kurikulum,
            .predikat-badge {
                background: #003366 !important;
                color: white !important;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print
        </button>
        <button class="btn-close" onclick="window.close()">
            ✖️ Close
        </button>
    </div>

    <!-- Certificate -->
    <div class="certificate">
        <!-- Tahun Kurikulum (KANAN ATAS) -->
        <div class="tahun-kurikulum">
            KURIKULUM <?php echo $tahun_kurikulum; ?>
        </div>
        
        <!-- School Header (TENGAH ATAS) -->
        <div class="school-header">
            <h1>MTSN 1 LEBAK</h1>
            <div class="predikat-badge"><?php echo strtoupper($predikat_sekolah); ?></div>
            <p>Jl. Raya Rangkasbitung, Lebak, Banten</p>
        </div>
        
        <!-- Main Content (TENGAH) -->
        <div class="cert-main">
            <h1 class="cert-title">CERTIFICATE</h1>
            <p class="cert-subtitle">of Achievement</p>
            
            <p class="cert-text">This is to certify that</p>
            
            <div class="student-name"><?php echo strtoupper($sertifikat['name']); ?></div>
            
            <div class="student-details">
                <span><strong>NISN:</strong> <?php echo $sertifikat['nisn']; ?></span>
                <span><strong>Kelas:</strong> <?php echo $sertifikat['kelas']; ?></span>
            </div>
            
            <p class="cert-text">
                Telah berpartisipasi aktif dan menunjukkan dedikasi luar biasa<br>
                dalam kegiatan ekstrakurikuler
            </p>
            
            <div class="eskul-name"><?php echo strtoupper($sertifikat['nama_ekskul']); ?></div>
            
            <?php if ($sertifikat['nilai']): ?>
            <div class="grade-section">
                <div class="nilai-display nilai-<?php echo $sertifikat['nilai']; ?>">
                    ⭐ NILAI <?php echo $sertifikat['nilai']; ?> ⭐
                </div>
               
                <?php if ($sertifikat['catatan_pembina']): ?>
                <div class="catatan-pembina">
                    <strong>Catatan Pembina:</strong><br>
                    "<?php echo $sertifikat['catatan_pembina']; ?>"
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Signature (KIRI BAWAH) -->
        <div class="signature-area">
            <div class="signature-block">
                <div class="sig-location">Lebak, <?php echo formatTanggal($sertifikat['tanggal_terbit']); ?></div>
                <div class="sig-title">Ketua Kurikulum</div>
                <!-- Digital Signature Image -->
                <img src="<?php echo BASE_URL; ?>assets/img/stempel.jpg" alt="Signature" class="sig-image">
                <div class="sig-name">Fajar Satria Utama</div>
                <div class="sig-nip">NIP. 198505152010011023</div>
            </div>
        </div>
        
        <!-- Certificate Number (TENGAH BAWAH) -->
        <div class="cert-number">
            Certificate No: <strong><?php echo $sertifikat['nomor_sertifikat']; ?></strong>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
<?php endif; ?>
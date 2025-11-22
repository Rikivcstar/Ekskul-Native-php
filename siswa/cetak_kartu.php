<?php
// siswa/cetak_kartu.php
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$current_user = getCurrentUser();
$anggota_id = $_GET['id'] ?? null;

if (!$anggota_id) {
    die('ID Anggota tidak ditemukan');
}

// Ambil data anggota
$data = query("
    SELECT 
        ae.id as anggota_id,
        ae.tanggal_daftar,
        ae.tanggal_diterima,
        u.id as user_id,
        u.name,
        u.nis,
        u.kelas,
        u.foto,
        u.jenis_kelamin,
        e.id as ekstrakurikuler_id,
        e.nama_ekskul,
        p.name as nama_pembina
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    LEFT JOIN users p ON e.pembina_id = p.id
    WHERE ae.id = ? AND ae.user_id = ? AND ae.status = 'diterima'
", [$anggota_id, $current_user['id']], 'ii')->fetch_assoc();

if (!$data) {
    die('Data tidak ditemukan atau Anda tidak memiliki akses');
}

// Path foto
$foto_url = BASE_URL . 'assets/img/default-avatar.png';
if ($data['foto'] && file_exists('../' . $data['foto'])) {
    $foto_url = BASE_URL . $data['foto'];
}

// Logo sekolah - sesuaikan path dengan lokasi file logo Anda
$logo_url = BASE_URL . 'assets/images/logo MTSN1.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Anggota - <?php echo htmlspecialchars($data['nama_ekskul']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f0f0f0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn {
            background: #198754;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #157347;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .card-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        /* Ukuran Kartu Credit Card: 85.6mm x 53.98mm */
        .card {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            overflow: hidden;       
        }
        
        /* KARTU DEPAN */
        .card-front {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            position: relative;
        }
        
        .header {
            background: rgba(0,0,0,0.2);
            padding: 6px 8px;
            text-align: center;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .logo-container {
            width: 35px;
            height: 35px;
            flex-shrink: 0;
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-text {
            flex: 1;
        }
        
        .header h1 {
            font-size: 13px;
            font-weight: 700;
            margin-right: 30px;
            letter-spacing: 0.5px;
        }
        
        .header p {
            font-size: 7px;
            margin-right: 30px;
            letter-spacing: 0.8px;
        }
        
        .content-area {
            background: white;
            margin: 5px;
            padding: 8px;
            height: 145px;
            display: flex;
            gap: 8px;
        }
        
        .photo-section {
            width: 75px;
            height: 100px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid #e0e0e0;
        }
        
        .photo-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-placeholder {
            width: 100%;
            height: 100%;
            background: #198754;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 700;
        }
        
        .info-section {
            flex: 1;
            min-width: 0;
        }
        
        .eskul-name {
            font-size: 10px;
            font-weight: 700;
            color: #198754;
            margin-bottom: 5px;
            border-bottom: 2px solid #198754;
            padding-bottom: 2px;
            word-wrap: break-word;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        
        .info-row {
            margin-bottom: 3px;
            font-size: 8px;
            display: flex;
            line-height: 1.4;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 7px;
            width: 50px;
            flex-shrink: 0;
            font-weight: 400;
        }
        
        .info-value {
            color: #212529;
            font-weight: 600;
            font-size: 8px;
            word-wrap: break-word;
        }
        
        .footer-info {
            position: absolute;
            bottom: 13px;
            left: 13px;
            right: 13px;
            display: flex;
            justify-content: space-between;
            font-size: 7px;
            color: white;
        }
        
        .pembina {
            font-size: 7px;
            max-width: 120px;
        }
        
        .pembina-label {
            color: rgba(255,255,255,0.8);
            font-size: 6px;
            margin-bottom: 1px;
        }
        
        .no-anggota {
            font-size: 6px;
            text-align: right;
        }
        
        .no-anggota strong {
            font-size: 8px;
        }
        
        .footer-strip {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            padding: 3px;
            text-align: center;
            color: black;
            font-size: 6px;
        }
        
        /* KARTU BELAKANG */
        .card-back {
            background: #dcfce7;
            padding: 8px;
        }
        
        .back-border {
            border: 2px solid #198754;
            border-radius: 6px;
            padding: 8px;
            height: 100%;
        }
        
        .back-title {
            color: #198754;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
            border-bottom: 2px solid #198754;
            padding-bottom: 3px;
            letter-spacing: 0.5px;
        }
        
        .kewajiban-list {
            margin: 6px 0;
        }
        
        .kewajiban-item {
            font-size: 7px;
            margin-bottom: 3px;
            line-height: 1.3;
            display: flex;
        }
        
        .kewajiban-item .number {
            color: #198754;
            font-weight: 700;
            margin-right: 4px;
            min-width: 12px;
            flex-shrink: 0;
        }
        
        .contact-box {
            background: #198754;
            color: white;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            margin-top: 1rem;
        }
        
        .contact-box h3 {
            font-size: 9px;
            margin-bottom: 2px;
            font-weight: 700;
        }
        
        .contact-box p {
            font-size: 6px;
            margin: 1px 0;
            line-height: 1.3;
        }
        
        /* PRINT STYLES */
        @media print {
            @page {
                size: 85.6mm 53.98mm;
                margin: 0;
            }
            
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .card-container {
                gap: 0;
                margin: 0;
                padding: 0;
                display: block;
            }
            
            .card {
                box-shadow: none;
                page-break-inside: avoid;
                page-break-after: always;
                margin: 0;
                width: 85.6mm;
                height: 53.98mm;
            }
            
            .card-back {
                page-break-before: always;
            }
            
            /* Pastikan semua elemen tercetak */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h2 style="color: #198754; margin-bottom: 15px;">
            🎴 Kartu Anggota Ekstrakurikuler
        </h2>
        <button onclick="window.print()" class="btn">
            🖨️ Cetak Kartu
        </button>
        <a href="<?php echo BASE_URL; ?>siswa/profil.php" class="btn btn-secondary">
            ← Kembali ke Profil
        </a>
        <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
            💡 Tips: Gunakan printer dengan ukuran kertas custom (85.6mm x 53.98mm) atau cetak di kertas A4 lalu potong sesuai ukuran kartu
        </p>
    </div>
    
    <div class="card-container">
        <!-- KARTU DEPAN -->
        <div class="card card-front">
            <div class="header">
                <div class="logo-container">
                    <img src="<?php echo $logo_url; ?>" alt="Logo MTsN 1 Lebak">
                </div>
                <div class="header-text">
                    <h1>MTsN 1 LEBAK</h1>
                    <p>KARTU ANGGOTA EKSTRAKURIKULER</p>
                </div>
            </div>
            
            <div class="content-area">
                <div class="photo-section">
                    <?php if ($data['foto'] && file_exists('../' . $data['foto'])): ?>
                        <img src="<?php echo $foto_url; ?>" alt="Foto <?php echo htmlspecialchars($data['name']); ?>">
                    <?php else: ?>
                        <div class="photo-placeholder">
                            <?php echo strtoupper(substr($data['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <div class="eskul-name"><?php echo htmlspecialchars($data['nama_ekskul']); ?></div>
                    
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value">: <?php echo htmlspecialchars($data['name']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">NIS</span>
                        <span class="info-value">: <?php echo htmlspecialchars($data['nis']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Kelas</span>
                        <span class="info-value">: <?php echo htmlspecialchars($data['kelas']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Terdaftar</span>
                        <span class="info-value">: <?php echo formatTanggal($data['tanggal_diterima'] ?? $data['tanggal_daftar']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="footer-info">
                <div class="pembina">
                    <div class="pembina-label">Pembina:</div>
                    <strong><?php echo htmlspecialchars($data['nama_pembina'] ?? '-'); ?></strong>
                </div>
                
                <div class="no-anggota">
                    No. Anggota:<br>
                    <strong>ESKUL-<?php echo str_pad($data['anggota_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                </div>
            </div>
            
            <div class="footer-strip">
                Kartu ini adalah bukti keanggotaan resmi ekstrakurikuler
            </div>
        </div>
        
        <!-- KARTU BELAKANG -->
        <div class="card card-back">
            <div class="back-border">
                <div class="back-title">KEWAJIBAN ANGGOTA</div>
                
                <div class="kewajiban-list">
                    <div class="kewajiban-item">
                        <span class="number">1.</span>
                        <span>Hadir tepat waktu setiap jadwal latihan</span>
                    </div>
                    <div class="kewajiban-item">
                        <span class="number">2.</span>
                        <span>Mengikuti seluruh program kegiatan</span>
                    </div>
                    <div class="kewajiban-item">
                        <span class="number">3.</span>
                        <span>Menjaga nama baik ekstrakurikuler</span>
                    </div>
                    <div class="kewajiban-item">
                        <span class="number">4.</span>
                        <span>Mematuhi tata tertib yang berlaku</span>
                    </div>
                    <div class="kewajiban-item">
                        <span class="number">5.</span>
                        <span>Menjaga dan merawat fasilitas bersama</span>
                    </div>
                </div>
                
                <div class="contact-box">
                    <h3>MTsN 1 LEBAK</h3>
                    <p>Jl. Raya Rangkasbitung, Lebak, Banten</p>
                    <p>Telp: (0252) 123456 | Email: info@mtsn1lebak.sch.id</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
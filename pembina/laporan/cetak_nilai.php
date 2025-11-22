<?php
// pembina/laporan/cetak_nilai.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$eskul_id = $_GET['eskul_id'] ?? 0;

if (!$eskul_id) {
    die("ID Ekstrakurikuler tidak valid");
}

// Cek apakah pembina berhak akses eskul ini
$eskul = query("
    SELECT e.*, u.name as nama_pembina
    FROM ekstrakurikulers e
    JOIN users u ON e.pembina_id = u.id
    WHERE e.id = ? AND e.pembina_id = ?
", [$eskul_id, $current_user['id']], 'ii')->fetch_assoc();

if (!$eskul) {
    die("Anda tidak memiliki akses ke ekstrakurikuler ini");
}

// Ambil data nilai anggota
$nilai = query("
    SELECT 
        u.nis,
        COALESCE(u.name) as nama_siswa,
        ae.nilai,
        ae.tanggal_daftar
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    ORDER BY u.nis ASC
", [$eskul_id], 'i');

$total_anggota = $nilai->num_rows;

// Hitung statistik nilai
$nilai->data_seek(0);
$sudah_dinilai = 0;
$belum_dinilai = 0;

while ($row = $nilai->fetch_assoc()) {
    if (!empty($row['nilai'])) {
        $sudah_dinilai++;
    } else {
        $belum_dinilai++;
    }
}
$nilai->data_seek(0);

$tanggal_cetak = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Nilai - <?= htmlspecialchars($eskul['nama_ekskul']) ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 5px 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        
        .info-section {
            margin: 20px 0;
            line-height: 1.8;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            width: 180px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table th, table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background: #333;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }
        
        .signature-box {
            text-align: center;
            width: 250px;
        }
        
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
            margin-right: 10px;
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
        
        .timestamp {
            text-align: right;
            font-size: 11px;
            color: #666;
            margin-top: 10px;
        }
        
        .summary-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak / Print</button>
        <button class="btn-print" style="background: #6c757d;" onclick="window.close()">✕ Tutup</button>
    </div>

    <div class="header">
        <h1>LAPORAN NILAI EKSTRAKURIKULER</h1>
        <h2>SMK/SMA [NAMA SEKOLAH]</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Nama Ekstrakurikuler</div>
            <div class="info-value">: <?= htmlspecialchars($eskul['nama_ekskul']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Pembina</div>
            <div class="info-value">: <?= htmlspecialchars($eskul['nama_pembina']) ?></div>
        </div>
      
        <div class="info-row">
            <div class="info-label">Total Anggota</div>
            <div class="info-value">: <strong><?= $total_anggota ?> siswa</strong></div>
        </div>
    </div>

    <div class="summary-box">
        <div class="stat-item">
            <div class="stat-number" style="color: #28a745;"><?= $sudah_dinilai ?></div>
            <div class="stat-label">Sudah Dinilai</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" style="color: #dc3545;"><?= $belum_dinilai ?></div>
            <div class="stat-label">Belum Dinilai</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= $total_anggota ?></div>
            <div class="stat-label">Total Anggota</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">NIS</th>
                <th>Nama Lengkap</th>
                <th style="width: 120px;">Tanggal Masuk</th>
                <th style="width: 100px;">Nilai</th>
                <th style="width: 120px;">Predikat</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_anggota == 0): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        Belum ada anggota yang terdaftar
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $no = 1; 
                while ($row = $nilai->fetch_assoc()): 
                    // Tentukan predikat berdasarkan nilai
                    $predikat = '';
                    $nilai_angka = $row['nilai'];
                    
                    if (!empty($nilai_angka)) {
                        if ($nilai_angka >= 90) {
                            $predikat = 'Sangat Baik';
                        } elseif ($nilai_angka >= 80) {
                            $predikat = 'Baik';
                        } elseif ($nilai_angka >= 70) {
                            $predikat = 'Cukup';
                        } else {
                            $predikat = 'Kurang';
                        }
                    }
                ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td style="text-align: center;">
                            <?= date('d/m/Y', strtotime($row['tanggal_daftar'])) ?>
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 16px;">
                            <?= !empty($nilai_angka) ? $nilai_angka : '-' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= !empty($predikat) ? $predikat : '-' ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
        <strong>Keterangan Predikat:</strong><br>
        90-100: Sangat Baik | 80-89: Baik | 70-79: Cukup | &lt;70: Kurang
    </div>

    <div class="footer">
        <div class="signature-box">
            <div><?= date('d F Y') ?></div>
            <div style="font-weight: bold;">Pembina Ekstrakurikuler</div>
            <div class="signature-line">
                ( <?= htmlspecialchars($eskul['nama_pembina']) ?> )
            </div>
        </div>
    </div>

    <div class="timestamp">
        Dicetak pada: <?= $tanggal_cetak ?> WIB
    </div>

</body>
</html>
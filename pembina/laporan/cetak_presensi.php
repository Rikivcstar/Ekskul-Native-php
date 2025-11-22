<?php
// pembina/laporan/cetak_presensi.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$eskul_id = $_GET['eskul_id'] ?? 0;
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

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

// Ambil data presensi per siswa
$rekap = query("
    SELECT 
        u.nis,
        COALESCE(u.name) as nama_siswa,
        COUNT(p.id) as total_pertemuan,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN p.status = 'alpha' THEN 1 ELSE 0 END) as alpha,
        ROUND((SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(p.id) * 100), 2) as persentase
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    LEFT JOIN presensis p ON ae.id = p.anggota_id AND p.tanggal BETWEEN ? AND ?
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    GROUP BY ae.id, u.nis, nama_siswa
    ORDER BY u.nis ASC
", [$dari, $sampai, $eskul_id], 'ssi');

$total_siswa = $rekap->num_rows;
$tanggal_cetak = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi - <?= htmlspecialchars($eskul['nama_ekskul']) ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1100px;
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
            font-size: 12px;
        }
        
        table th, table td {
            border: 1px solid #333;
            padding: 6px;
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
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        
        .stat-box {
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
        <h1>LAPORAN REKAP PRESENSI</h1>
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
            <div class="info-label">Periode</div>
            <div class="info-value">: <?= date('d F Y', strtotime($dari)) ?> s/d <?= date('d F Y', strtotime($sampai)) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Siswa</div>
            <div class="info-value">: <strong><?= $total_siswa ?> siswa</strong></div>
        </div>
    </div>

    <?php
    // Hitung total keseluruhan
    $rekap->data_seek(0);
    $total_hadir = 0;
    $total_izin = 0;
    $total_sakit = 0;
    $total_alpha = 0;
    $total_pertemuan_all = 0;
    
    while ($row = $rekap->fetch_assoc()) {
        $total_hadir += $row['hadir'];
        $total_izin += $row['izin'];
        $total_sakit += $row['sakit'];
        $total_alpha += $row['alpha'];
        $total_pertemuan_all += $row['total_pertemuan'];
    }
    $rekap->data_seek(0);
    ?>

    <div class="summary-stats">
        <div class="stat-box">
            <div class="stat-number" style="color: #28a745;"><?= $total_hadir ?></div>
            <div class="stat-label">Total Hadir</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #ffc107;"><?= $total_izin ?></div>
            <div class="stat-label">Total Izin</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #17a2b8;"><?= $total_sakit ?></div>
            <div class="stat-label">Total Sakit</div>
        </div>
        <div class="stat-box">
            <div class="stat-number" style="color: #dc3545;"><?= $total_alpha ?></div>
            <div class="stat-label">Total Alpha</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">NIS</th>
                <th>Nama Lengkap</th>
                <th style="width: 60px;">Hadir</th>
                <th style="width: 60px;">Izin</th>
                <th style="width: 60px;">Sakit</th>
                <th style="width: 60px;">Alpha</th>
                <th style="width: 60px;">Total</th>
                <th style="width: 80px;">Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_siswa == 0): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        Tidak ada data presensi pada periode ini
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; while ($row = $rekap->fetch_assoc()): ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td style="text-align: center; color: #28a745; font-weight: bold;"><?= $row['hadir'] ?></td>
                        <td style="text-align: center; color: #ffc107;"><?= $row['izin'] ?></td>
                        <td style="text-align: center; color: #17a2b8;"><?= $row['sakit'] ?></td>
                        <td style="text-align: center; color: #dc3545;"><?= $row['alpha'] ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $row['total_pertemuan'] ?></td>
                        <td style="text-align: center; font-weight: bold;">
                            <?= $row['persentase'] ?? 0 ?>%
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

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
<?php
// admin/laporan/cetak_anggota.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$eskul_id = $_GET['eskul_id'] ?? 0;

// Ambil data eskul
$eskul = query("SELECT e.*, u.name as nama_pembina FROM ekstrakurikulers e LEFT JOIN users u ON e.pembina_id = u.id WHERE e.id = ?", [$eskul_id], 'i');

if (!$eskul || $eskul->num_rows == 0) {
    die('Ekstrakurikuler tidak ditemukan!');
}

$data_eskul = $eskul->fetch_assoc();

// Ambil data anggota
$anggota = query("
    SELECT ae.*, u.name, u.nis, u.kelas, u.jenis_kelamin, u.no_hp, u.alamat
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    ORDER BY u.name
", [$eskul_id], 'i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Anggota - <?php echo $data_eskul['nama_ekskul']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
        body { font-family: Arial, sans-serif; }
        .kop-surat { text-align: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h3 { margin: 5px 0; font-weight: bold; }
        .kop-surat p { margin: 2px 0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>

        <!-- Kop Surat -->
        <div class="kop-surat">
            <h3>MTsN 1 LEBAK</h3>
            <p>Jl. Raya Rangkasbitung, Lebak, Banten</p>
            <p>Telp: (0252) 123456 | Email: info@mtsn1lebak.sch.id</p>
        </div>

        <h4 class="text-center mb-4">LAPORAN DAFTAR ANGGOTA EKSTRAKURIKULER</h4>

        <table class="mb-4" style="width: 100%;">
            <tr>
                <td width="150"><strong>Ekstrakurikuler</strong></td>
                <td>: <?php echo $data_eskul['nama_ekskul']; ?></td>
            </tr>
            <tr>
                <td><strong>Pembina</strong></td>
                <td>: <?php echo $data_eskul['nama_pembina'] ?? '-'; ?></td>
            </tr>
            <tr>
                <td><strong>Kuota</strong></td>
                <td>: <?php echo $anggota->num_rows; ?> / <?php echo $data_eskul['kuota']; ?> orang</td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: <?php echo date('d F Y'); ?></td>
            </tr>
        </table>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">NIS</th>
                    <th width="25%">Nama Lengkap</th>
                    <th width="10%">Kelas</th>
                    <th width="10%">L/P</th>
                    <th width="15%">No HP</th>
                    <th width="20%">Tanggal Daftar</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($anggota && $anggota->num_rows > 0):
                    $no = 1;
                    while ($row = $anggota->fetch_assoc()):
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['nis']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td class="text-center"><?php echo $row['kelas']; ?></td>
                    <td class="text-center"><?php echo $row['jenis_kelamin']; ?></td>
                    <td><?php echo $row['no_hp']; ?></td>
                    <td><?php echo formatTanggal($row['tanggal_daftar']); ?></td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada anggota</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-5">
            <div class="col-6"></div>
            <div class="col-6 text-center">
                <p>Lebak, <?php echo date('d F Y'); ?></p>
                <p><strong>Pembina Ekstrakurikuler</strong></p>
                <br><br><br>
                <p><strong><u><?php echo $data_eskul['nama_pembina'] ?? '________________'; ?></u></strong></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
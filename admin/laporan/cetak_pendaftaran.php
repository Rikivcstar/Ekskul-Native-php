<?php
// admin/laporan/cetak_pendaftaran.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Ambil data pendaftaran
$pendaftaran = query("
    SELECT ae.*, u.name, u.nis, u.kelas, u.no_hp, e.nama_ekskul
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.tanggal_daftar BETWEEN ? AND ?
    ORDER BY ae.tanggal_daftar DESC, e.nama_ekskul
", [$dari, $sampai], 'ss');

// Hitung statistik
$stats = query("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'diterima' THEN 1 END) as diterima,
        COUNT(CASE WHEN status = 'ditolak' THEN 1 END) as ditolak
    FROM anggota_ekskul
    WHERE tanggal_daftar BETWEEN ? AND ?
", [$dari, $sampai], 'ss')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendaftaran</title>
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

        <h4 class="text-center mb-4">LAPORAN PENDAFTARAN EKSTRAKURIKULER</h4>

        <table class="mb-4" style="width: 100%;">
            <tr>
                <td width="200"><strong>Periode</strong></td>
                <td>: <?php echo formatTanggal($dari); ?> s/d <?php echo formatTanggal($sampai); ?></td>
            </tr>
            <tr>
                <td><strong>Total Pendaftaran</strong></td>
                <td>: <?php echo $stats['total']; ?> pendaftar</td>
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
                    <th width="12%">Tanggal</th>
                    <th width="15%">NIS</th>
                    <th width="20%">Nama</th>
                    <th width="8%">Kelas</th>
                    <th width="20%">Ekstrakurikuler</th>
                    <th width="10%">No HP</th>
                    <th width="10%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($pendaftaran && $pendaftaran->num_rows > 0):
                    $no = 1;
                    while ($row = $pendaftaran->fetch_assoc()):
                        $badge_class = [
                            'pending' => 'warning',
                            'diterima' => 'success',
                            'ditolak' => 'danger'
                        ];
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar'])); ?></td>
                    <td><?php echo $row['nis']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td class="text-center"><?php echo $row['kelas']; ?></td>
                    <td><?php echo $row['nama_ekskul']; ?></td>
                    <td><?php echo $row['no_hp']; ?></td>
                    <td class="text-center">
                        <span style="background: #<?php echo $badge_class[$row['status']] == 'warning' ? 'ffc107' : ($badge_class[$row['status']] == 'success' ? '198754' : 'dc3545'); ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data pendaftaran pada periode ini</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-4">
            <h6><strong>Ringkasan Status Pendaftaran:</strong></h6>
            <table class="table table-bordered" style="width: 400px;">
                <tr>
                    <td><strong>Pending</strong></td>
                    <td class="text-center"><strong><?php echo $stats['pending']; ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Diterima</strong></td>
                    <td class="text-center"><strong><?php echo $stats['diterima']; ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Ditolak</strong></td>
                    <td class="text-center"><strong><?php echo $stats['ditolak']; ?></strong></td>
                </tr>
                <tr class="table-secondary">
                    <td><strong>Total</strong></td>
                    <td class="text-center"><strong><?php echo $stats['total']; ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="row mt-5">
            <div class="col-6"></div>
            <div class="col-6 text-center">
                <p>Lebak, <?php echo date('d F Y'); ?></p>
                <p><strong>Koordinator Ekstrakurikuler</strong></p>
                <br><br><br>
                <p><strong><u>_______________________</u></strong></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
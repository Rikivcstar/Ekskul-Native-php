<?php
// admin/laporan/cetak_rekap.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

// Ambil rekap semua eskul
$rekap = query("
    SELECT 
        e.id,
        e.nama_ekskul,
        e.kuota,
        u.name as nama_pembina,
        COUNT(CASE WHEN ae.status = 'diterima' THEN 1 END) as total_anggota,
        COUNT(CASE WHEN ae.status = 'pending' THEN 1 END) as total_pending
    FROM ekstrakurikulers e
    LEFT JOIN users u ON e.pembina_id = u.id
    LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE e.status = 'aktif'
    GROUP BY e.id
    ORDER BY e.nama_ekskul
");

// Hitung total keseluruhan
$total_stats = query("
    SELECT 
        COUNT(DISTINCT e.id) as total_eskul,
        SUM(e.kuota) as total_kuota,
        COUNT(CASE WHEN ae.status = 'diterima' THEN 1 END) as total_anggota_semua,
        COUNT(CASE WHEN ae.status = 'pending' THEN 1 END) as total_pending_semua
    FROM ekstrakurikulers e
    LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE e.status = 'aktif'
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekap Ekstrakurikuler</title>
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

        <h4 class="text-center mb-4">LAPORAN REKAP EKSTRAKURIKULER</h4>

        <table class="mb-4" style="width: 100%;">
            <tr>
                <td width="200"><strong>Periode</strong></td>
                <td>: Tahun Ajaran <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></td>
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
                    <th width="25%">Nama Ekstrakurikuler</th>
                    <th width="20%">Pembina</th>
                    <th width="12%" class="text-center">Kuota</th>
                    <th width="12%" class="text-center">Anggota</th>
                    <th width="12%" class="text-center">Pending</th>
                    <th width="14%" class="text-center">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($rekap && $rekap->num_rows > 0):
                    $no = 1;
                    while ($row = $rekap->fetch_assoc()):
                        $persentase = $row['kuota'] > 0 ? round(($row['total_anggota'] / $row['kuota']) * 100) : 0;
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['nama_ekskul']; ?></td>
                    <td><?php echo $row['nama_pembina'] ?? '-'; ?></td>
                    <td class="text-center"><?php echo $row['kuota']; ?></td>
                    <td class="text-center"><strong><?php echo $row['total_anggota']; ?></strong></td>
                    <td class="text-center"><?php echo $row['total_pending']; ?></td>
                    <td class="text-center"><strong><?php echo $persentase; ?>%</strong></td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data</td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <th colspan="3" class="text-end">TOTAL:</th>
                    <th class="text-center"><?php echo $total_stats['total_kuota']; ?></th>
                    <th class="text-center"><?php echo $total_stats['total_anggota_semua']; ?></th>
                    <th class="text-center"><?php echo $total_stats['total_pending_semua']; ?></th>
                    <th class="text-center">
                        <?php echo $total_stats['total_kuota'] > 0 ? round(($total_stats['total_anggota_semua'] / $total_stats['total_kuota']) * 100) : 0; ?>%
                    </th>
                </tr>
            </tfoot>
        </table>

        <div class="mt-4">
            <h6><strong>Ringkasan:</strong></h6>
            <ul>
                <li>Total Ekstrakurikuler Aktif: <strong><?php echo $total_stats['total_eskul']; ?></strong></li>
                <li>Total Kuota: <strong><?php echo $total_stats['total_kuota']; ?></strong> orang</li>
                <li>Total Anggota Aktif: <strong><?php echo $total_stats['total_anggota_semua']; ?></strong> orang</li>
                <li>Total Pendaftaran Pending: <strong><?php echo $total_stats['total_pending_semua']; ?></strong> orang</li>
            </ul>
        </div>

        <div class="row mt-5">
            <div class="col-6"></div>
            <div class="col-6 text-center">
                <p>Lebak, <?php echo date('d F Y'); ?></p>
                <p><strong>Kepala Madrasah</strong></p>
                <br><br><br>
                <p><strong><u>Drs. H. Ahmad Yani, M.Pd</u></strong></p>
                <p>NIP. 197001011997031001</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
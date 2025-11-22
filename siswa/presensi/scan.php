<?php
// siswa/scan_qr.php
session_start();
// Anda mungkin ingin menyertakan header dan shell di sini
require_once "../../config/database.php"; 
$page_title = 'Scan QR Presensi';
// Misalnya, Anda menggunakan layout berry_siswa_head.php
// require_once '../../includes/berry_siswa_head.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        #reader {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            border: 2px solid #007bff;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center mt-4">Pindai QR Code Presensi</h3>
        <p class="text-center text-muted">Arahkan kamera ke QR Code yang diberikan oleh Pembina.</p>
        
        <div id="reader"></div>

        <div class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>siswa/dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>

    <script>
        // Fungsi yang akan dipanggil ketika QR Code berhasil dipindai
        function onScanSuccess(decodedText, decodedResult) {
            // 1. Hentikan scanner setelah berhasil memindai
            html5QrcodeScanner.clear();
            
            // 2. Redirect ke script pemrosesan PHP Anda (scan.php)
            // Asumsi: Isi QR Code adalah token mentah yang Anda butuhkan.
            const token = decodedText;
            
            // URL target: script pemrosesan presensi Anda
            const processUrl = '<?php echo BASE_URL; ?>siswa/presensi/scan.php?token=' + token;

            // Alihkan siswa ke halaman pemrosesan
            window.location.href = processUrl;
        }

        // Fungsi yang akan dipanggil jika terjadi error (opsional)
        function onScanFailure(error) {
            // console.warn(`Code scan error = ${error}`);
            // Biarkan kosong agar proses scan tetap berjalan tanpa notifikasi error mengganggu
        }

        // Inisialisasi Html5QrcodeScanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", // ID dari elemen div tempat scanner ditampilkan
            { 
                fps: 10, 
                qrbox: {width: 250, height: 250} 
            },
            /* verbose= */ false
        );

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
    
    </body>
</html>
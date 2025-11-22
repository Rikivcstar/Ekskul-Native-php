<?php
// includes/berry_siswa_head.php
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>Portal Siswa MTsN 1 Lebak</title>
  <meta name="description" content="Portal siswa ekstrakurikuler MTsN 1 Lebak" />
  <meta name="keywords" content="siswa, ekstrakurikuler, dashboard" />
  <meta name="author" content="MTsN 1 Lebak" />

  <link rel="icon" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/images/favicon.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" id="main-font-link" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/feather.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/material.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/css/style.css" id="main-style-link" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/css/style-preset.css" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f3f6fb;
    }
    .pc-sidebar {
      background: #0f172a;
      color: #e2e8f0;
    }
    .pc-sidebar .pc-item .pc-link {
      color: #cbd5f5;
      border-radius: 12px;
    }
    .pc-sidebar .pc-item .pc-link .pc-micon {
      color: #38bdf8;
    }
    .pc-sidebar .pc-item.active > .pc-link,
    .pc-sidebar .pc-item .pc-link:hover {
      background: rgba(14,165,233,.15);
      color: #ffffff;
    }
    .pc-sidebar .pc-item.active .pc-micon,
    .pc-sidebar .pc-item .pc-link:hover .pc-micon {
      color: #38bdf8;
    }
    .pc-header {
      border: 0;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(20px);
      box-shadow: 0 10px 30px rgba(15,23,42,0.08);
    }
    .student-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      border-radius: 999px;
      background: #dbeafe;
      color: #0f172a;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .pc-container {
      background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      min-height: calc(100vh - 70px);
    }
    .pc-content {
      padding-top: 30px;
      padding-bottom: 30px;
    }
    .pc-sidebar-overlay {
      display:none;
    }
    @media (max-width: 1024px){
      .pc-sidebar-overlay{
        display:block;
      }
    }



  </style>
</head>
<body>


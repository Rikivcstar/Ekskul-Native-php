<?php
// includes/berry_head.php
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>Berry Admin</title>
  <meta name="description" content="Admin panel" />
  <meta name="keywords" content="admin, dashboard, berry" />
  <meta name="author" content="codedthemes" />
  
  <link rel="icon" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/images/favicon.svg" type="image/x-icon" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" id="main-font-link" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/feather.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/fonts/material.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/css/style.css" id="main-style-link" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/berry/dist/assets/css/style-preset.css" />
  <style>
    .pc-header .input-group { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; }
    .pc-header .input-group .form-control { box-shadow:none; }
    .pc-header .pc-head-link { color: inherit; }
    .pc-sidebar .pc-item.active > .pc-link { background:#f1eeff; color:#5e35b1; border-radius:12px; }

    /* Soft icon pill buttons */
    .icon-pill { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:12px; background:#efe7ff; color:#6c4edb; transition:all .2s; }
    .icon-pill:hover { filter:brightness(.96); }

    /* Search pill */
    .header-search { display:flex; align-items:center; gap:10px; min-width:320px; background:#fff; border:1px solid #e9ecef; border-radius:16px; padding:6px 10px; }
    .header-search .search-icon { color:#9aa1b7; }
    .header-search input { border:0; outline:0; width:100%; }
    .header-search input::placeholder { color:#aab0c5; }
    .header-search .filter-pill { width:36px; height:36px; border-radius:12px; background:#efe7ff; color:#6c4edb; display:flex; align-items:center; justify-content:center; }

    /* Avatar pill */
    .avatar-pill { display:flex; align-items:center; gap:8px; background:#d6ebff; padding:4px 8px; border-radius:20px; }
    .avatar-pill .settings { color:#2b7fd6; }
    @media (max-width: 768px){ .header-search{ min-width:0; width:100%; } }

    /* Mobile sidebar close button */
    .btn-close-sidebar { display:none; align-items:center; justify-content:center; width:36px; height:36px; border-radius:12px; background:#efe7ff; color:#6c4edb; }
    @media (max-width:1024px){ .btn-close-sidebar{ display:inline-flex; } }

    /* Sidebar overlay for mobile */
    .pc-sidebar-overlay
      {
        display:none; position:fixed;
        inset:0; background:rgba(0,0,0,.25); 
        z-index:1024;
      }
    @media (max-width:1024px)
    { 
      .pc-sidebar.mob-sidebar-active + .pc-sidebar-overlay
      { 
        display:block; 
      } 
    }
    #grafikPenilaian {
    margin:10px auto;
    max-height: 390px ;
  }

    
  </style>
</head>
<body>

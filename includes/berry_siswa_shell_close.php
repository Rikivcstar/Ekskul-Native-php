<?php
// includes/berry_siswa_shell_close.php
?>
  </div>
</div>

<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row">
      <div class="col-sm-6 my-1">
        <p class="m-0 text-muted">© <?php echo date('Y'); ?> MTsN 1 Lebak - Portal Siswa</p>
      </div>
      <div class="col-sm-6 my-1 text-sm-end">
        <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">Kembali ke Beranda</a>
      </div>
    </div>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/popper.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/simplebar.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/icon/custom-font.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/script.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/theme.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/feather.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
      const sidebar = document.querySelector(".pc-sidebar");
      const overlay = document.getElementById("pc-sidebar-overlay");

      const btnHide = document.getElementById("sidebar-hide");
      const btnClose = document.getElementById("sidebar-close");

      if (btnHide) btnHide.onclick = () => {
          if (window.innerWidth <= 1024) {
              sidebar.classList.add("mob-sidebar-active");
              overlay.style.display = "block";
          }
      };

      if (btnClose) btnClose.onclick = () => {
          sidebar.classList.remove("mob-sidebar-active");
          overlay.style.display = "none";
      };

      if (overlay) overlay.onclick = () => {
          sidebar.classList.remove("mob-sidebar-active");
          overlay.style.display = "none";
      };
  });
</script>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/custom/siswa.css">

</body>
</html>


<?php
// includes/berry_shell_close.php
?>
</div></div>

<footer class="pc-footer">
  <div class="footer-wrapper container-fluid">
    <div class="row">
      <div class="col-sm-6 my-1">
        <p class="m-0">Created by <a href="https://themeforest.net/user/codedthemes" target="_blank">Unpam - TPLK003</a></p>
      </div>
      
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/popper.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/simplebar.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/icon/custom-font.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/script.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/theme.js"></script>
<script src="<?php echo BASE_URL; ?>assets/berry/dist/assets/js/plugins/feather.min.js"></script>

<script>
     // Plugin animasi custom
    const smoothAnimationPlugin = {
        id: "smoothAnimation",
        beforeDatasetDraw(chart, args, options) {
            const { ctx } = chart;
            ctx.save();
        },
        afterDatasetDraw(chart, args, options) {
            const { ctx } = chart;
            ctx.restore();
        }
    };

    Chart.register(smoothAnimationPlugin);

     const ctx2 = document.getElementById("grafikEkskul").getContext("2d");

    new Chart(ctx2, {
        type: "line",
        data: {
            labels: labelEskul,
            datasets: [{
                label: "Anggota",
                data: dataEskul,
                borderColor: "rgba(16, 185, 129, 1)",
                backgroundColor: "rgba(16, 185, 129, 0.35)",
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            animation: {
                duration: 1500,
                easing: "easeOutQuart"
            }
        }
    });

    const ctx1 = document.getElementById("grafikStatistik").getContext("2d");
    new Chart(ctx1, {
        type: "bar",
        data: {
            labels: ["Ekstrakurikuler", "Siswa", "Anggota", "Pembina"],
            datasets: [{
                label: "Total",
                data: [statistikData.eskul, statistikData.siswa, statistikData.anggota, statistikData.pembina],
                backgroundColor: "rgba(99, 102, 241, 0.8)",
                borderRadius: 8,
            }]
        },
        options: {
            animation: {
                duration: 1200,
                easing: "easeOutBounce"
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

   const ctx3 = document.getElementById("grafikPenilaian").getContext("2d");
    new Chart(ctx3, {
        type: "pie",
        data: {
            labels: ["Sudah Dinilai", "Belum Dinilai"],
            datasets: [{
                data: [penilaianData.sudah, penilaianData.belum],
                backgroundColor: [
                    "rgba(34,197,94,0.9)",
                    "rgba(239,68,68,0.9)"
                ]
            }]
        },
        options: {
            plugins: {
                legend: { position: "bottom" }
            },
            animation: {
                duration: 1500,
                easing: "easeOutQuart",
                animateRotate: true,
                animateScale: true
            }
        }
    });
</script>


<script>
  (function(){
    function bindSidebarToggle(){
      var btn = document.getElementById('sidebar-hide');
      var btnClose = document.getElementById('sidebar-close');
      var overlay = document.getElementById('pc-sidebar-overlay');
      var sidebar = document.querySelector('.pc-sidebar');
      if(!sidebar) return;
      // Toggle handler
      function toggleSidebar(e){
        if(e) e.preventDefault();
        if (window.innerWidth <= 1024) {
          sidebar.classList.toggle('mob-sidebar-active');
        } else {
          sidebar.classList.toggle('pc-sidebar-hide');
        }
      }
      // Open/close bindings (direct)
      if (btn && !btn.__pcBound) { btn.__pcBound = true; btn.addEventListener('click', toggleSidebar); }
      if (btnClose && !btnClose.__pcBound) { btnClose.__pcBound = true; btnClose.addEventListener('click', function(e){ e.preventDefault(); sidebar.classList.remove('mob-sidebar-active'); }); }
      if (overlay && !overlay.__pcBound) { overlay.__pcBound = true; overlay.addEventListener('click', function(){ sidebar.classList.remove('mob-sidebar-active'); }); }

      // Delegated click handlers (robust to DOM changes)
      document.addEventListener('click', function(ev){
        var t = ev.target;
        if (t.closest && t.closest('#sidebar-hide')) { toggleSidebar(ev); }
        if (t.closest && (t.closest('#sidebar-close') || t.closest('#pc-sidebar-overlay'))) {
          ev.preventDefault();
          sidebar.classList.remove('mob-sidebar-active');
        }
      }, true);
      // Escape key to close on mobile
      document.addEventListener('keydown', function(ev){ if(ev.key==='Escape'){ sidebar.classList.remove('mob-sidebar-active'); }});
    }
    if(document.readyState === 'loading'){
      document.addEventListener('DOMContentLoaded', bindSidebarToggle);
    } else {
      bindSidebarToggle();
    }
    window.addEventListener('resize', function(){
      var sidebar = document.querySelector('.pc-sidebar');
      if(!sidebar) return;
      if (window.innerWidth > 1024) {
        // ensure mobile state cleared, overlay hidden
        sidebar.classList.remove('mob-sidebar-active');
      }
    });
  })();
  </script>

</body>
</html>

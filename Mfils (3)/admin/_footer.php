</div><!-- /.page-content -->
  <footer style="text-align:center;padding:.9rem 1.75rem;font-size:.72rem;color:var(--muted);border-top:1px solid var(--border);background:#fff;margin-top:auto">
    <?= APP_NAME ?> &nbsp;·&nbsp; Admin Panel &nbsp;·&nbsp; <?= date('Y') ?>
    &nbsp;|&nbsp; PHP <?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?>
    &nbsp;|&nbsp; <span style="color:var(--success)">●</span> Connected
  </footer>
</div><!-- /.main-wrap -->
<script>
  // Live clock
  function tick() {
    const el = document.getElementById('liveTime');
    if (el) {
      el.textContent = new Date().toLocaleString('en-IN', {
        timeZone: 'Asia/Kolkata',
        day:'2-digit', month:'short', year:'numeric',
        hour:'2-digit', minute:'2-digit', second:'2-digit'
      });
    }
  }
  tick();
  setInterval(tick, 1000);
</script>
</body>
</html>
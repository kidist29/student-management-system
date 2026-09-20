<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
  // Render any server-side flash messages queued this request as toasts.
  window.__flashes = <?= json_encode(getFlashes()) ?>;
  document.addEventListener('DOMContentLoaded', function () {
    (window.__flashes || []).forEach(function (f) {
      showToast(f.message, f.type);
    });
  });
</script>
</body>
</html>

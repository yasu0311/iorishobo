<script>
(() => {
  const form = document.getElementById('product-file-form');
  if (!form) return;
  const maxBytes = parseInt(form.getAttribute('data-client-max-bytes'), 10);
  const msg = form.getAttribute('data-client-oversized-message') || '';
  const fileInput = form.querySelector('input[name="product_file"]');
  const errEl = document.getElementById('product-file-client-error');
  if (!fileInput || !Number.isFinite(maxBytes)) return;

  function clearErr() {
    if (!errEl) return;
    errEl.textContent = '';
    errEl.hidden = true;
  }

  function showErr(text) {
    if (errEl) {
      errEl.textContent = text;
      errEl.hidden = false;
    } else {
      window.alert(text);
    }
  }

  function validateProductFile() {
    clearErr();
    if (!fileInput.files || fileInput.files.length === 0) {
      return true;
    }
    const f = fileInput.files[0];
    if (f.size > maxBytes) {
      showErr(msg);
      return false;
    }
    return true;
  }

  fileInput.addEventListener('change', validateProductFile);
  form.addEventListener('submit', (e) => {
    if (!validateProductFile()) {
      e.preventDefault();
      fileInput.focus();
      return;
    }
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
    }
    if (typeof showSpinner === 'function') {
      showSpinner('ファイルをアップロードしています。しばらくお待ちください...');
    }
  });
})();
</script>

(() => {
  const select = document.querySelector('[data-checkout-shipping-select]');
  const feeDisplay = document.querySelector('[data-checkout-shipping-fee]');
  const notice = document.querySelector('[data-checkout-shipping-notice]');

  if (!select || !feeDisplay) {
    return;
  }

  const formatYen = (amount) =>
    `${Number(amount).toLocaleString('ja-JP')}円`;

  const updateShippingDisplay = () => {
    const option = select.selectedOptions[0];
    if (!option) {
      return;
    }

    const isFree = option.dataset.isFree === '1';
    const feeLabel = option.dataset.feeLabel || '—';
    const threshold = option.dataset.threshold;
    const remaining = option.dataset.remaining;

    feeDisplay.textContent = feeLabel;
    feeDisplay.classList.toggle('checkout-summary__shipping--free', isFree);

    if (!notice) {
      return;
    }

    if (isFree) {
      notice.innerHTML =
        '<span class="checkout-shipping-notice__free">この配送方法は<strong>送料無料</strong>です。</span>';
      return;
    }

    if (threshold !== '') {
      notice.innerHTML =
        `あと<strong>${formatYen(remaining)}</strong>で送料無料になります（商品合計${formatYen(threshold)}以上）。`;
      return;
    }

    notice.textContent = `この配送方法の送料は${feeLabel}です。`;
  };

  select.addEventListener('change', updateShippingDisplay);
})();

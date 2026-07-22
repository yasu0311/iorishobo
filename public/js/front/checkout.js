(() => {
  const formatYen = (amount) =>
    `${Number(amount).toLocaleString('ja-JP')}円`;

  const shippingSelect = document.querySelector('[data-checkout-shipping-select]');
  const shippingFeeDisplay = document.querySelector('[data-checkout-shipping-fee]');
  const shippingNotice = document.querySelector('[data-checkout-shipping-notice]');

  if (!shippingSelect || !shippingFeeDisplay) {
    return;
  }

  const updateShippingDisplay = () => {
    const option = shippingSelect.options[shippingSelect.selectedIndex];
    if (!option) {
      return;
    }

    const isFree = option.getAttribute('data-is-free') === '1';
    const feeLabel = option.getAttribute('data-fee-label') || '—';
    const threshold = option.getAttribute('data-threshold') || '';
    const remaining = option.getAttribute('data-remaining') || '';

    shippingFeeDisplay.textContent = feeLabel;
    shippingFeeDisplay.classList.toggle('checkout-summary__shipping--free', isFree);

    if (!shippingNotice) {
      return;
    }

    if (isFree) {
      shippingNotice.innerHTML =
        '<span class="checkout-shipping-notice__free">この配送方法は<strong>送料無料</strong>です。</span>';
      return;
    }

    if (threshold !== '') {
      shippingNotice.innerHTML =
        `あと<strong>${formatYen(remaining)}</strong>で送料無料になります（商品合計${formatYen(threshold)}以上）。`;
      return;
    }

    shippingNotice.textContent = `この配送方法の送料は${feeLabel}です。`;
  };

  shippingSelect.addEventListener('change', updateShippingDisplay);
  updateShippingDisplay();
})();

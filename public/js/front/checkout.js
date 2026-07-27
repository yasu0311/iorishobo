(() => {
  const formatYen = (amount) =>
    `${Number(amount).toLocaleString('ja-JP')}円`;

  const toHalfWidthDigitsAndHyphens = (value) => {
    const half = value.replace(/[０-９]/g, (ch) =>
      String.fromCharCode(ch.charCodeAt(0) - 0xfee0),
    );

    return half.replace(/[－−‐‒–—ー]/g, '-');
  };

  const normalizePostalCode = (value) =>
    toHalfWidthDigitsAndHyphens(value).replace(/[\s-]+/g, '');

  const normalizePhone = (value) =>
    toHalfWidthDigitsAndHyphens(value).replace(/\s+/g, '');

  document.querySelectorAll('[data-checkout-postal]').forEach((input) => {
    input.addEventListener('blur', () => {
      input.value = normalizePostalCode(input.value);
    });
  });

  document.querySelectorAll('[data-checkout-phone]').forEach((input) => {
    input.addEventListener('blur', () => {
      input.value = normalizePhone(input.value);
    });
  });

  const shippingSelect = document.querySelector('[data-checkout-shipping-select]');
  const shippingFeeDisplay = document.querySelector('[data-checkout-shipping-fee]');
  const shippingNotice = document.querySelector('[data-checkout-shipping-notice]');
  const paymentSelect = document.querySelector('[data-checkout-payment-select]');
  const paymentFeeRow = document.querySelector('[data-checkout-payment-fee-row]');
  const paymentFeeDisplay = document.querySelector('[data-checkout-payment-fee]');
  const codNotice = document.querySelector('[data-checkout-cod-notice]');

  const updateShippingDisplay = () => {
    if (!shippingSelect || !shippingFeeDisplay) {
      return;
    }

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

  const updatePaymentFee = () => {
    if (!paymentSelect || !paymentFeeRow || !paymentFeeDisplay) {
      return;
    }

    const option = paymentSelect.options[paymentSelect.selectedIndex];
    if (!option) {
      return;
    }

    const fee = Number(option.getAttribute('data-fee') || '0');
    paymentFeeDisplay.textContent = `${fee.toLocaleString('ja-JP')}円`;

    if (fee <= 0) {
      paymentFeeRow.setAttribute('hidden', 'hidden');
    } else {
      paymentFeeRow.removeAttribute('hidden');
    }
  };

  const syncCodAvailability = () => {
    if (!shippingSelect || !paymentSelect) {
      return;
    }

    const shippingOption = shippingSelect.options[shippingSelect.selectedIndex];
    const yuPackSlug = paymentSelect.getAttribute('data-yu-pack-slug') || 'yu-pack';
    const isYuPack = shippingOption?.getAttribute('data-slug') === yuPackSlug;
    const codOption = paymentSelect.querySelector('option[data-requires-yu-pack]');

    if (codOption) {
      codOption.disabled = !isYuPack;

      if (!isYuPack && paymentSelect.value === 'cod') {
        paymentSelect.value = 'stripe';
      }
    }

    if (codNotice) {
      if (isYuPack) {
        codNotice.setAttribute('hidden', 'hidden');
      } else {
        codNotice.removeAttribute('hidden');
      }
    }

    updatePaymentFee();
  };

  if (shippingSelect && shippingFeeDisplay) {
    shippingSelect.addEventListener('change', () => {
      updateShippingDisplay();
      syncCodAvailability();
    });
    updateShippingDisplay();
  }

  if (paymentSelect) {
    paymentSelect.addEventListener('change', updatePaymentFee);
    syncCodAvailability();
  }
})();

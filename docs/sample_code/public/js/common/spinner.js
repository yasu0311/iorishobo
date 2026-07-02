/**
 * 汎用スピナー表示ユーティリティ
 * 決済待ち・API通信中など、処理待ち状態の表示に使用
 *
 * 使用方法:
 *   showSpinner('決済処理中です...');
 *   hideSpinner();
 */
(function () {
  const DEFAULT_ID = 'spinner-overlay';
  const DEFAULT_MESSAGE = '処理中です。しばらくお待ちください...';

  function getSpinner(id) {
    return document.getElementById(id || DEFAULT_ID);
  }

  window.showSpinner = function (message, id) {
    const el = getSpinner(id);
    if (!el) return;
    const msgEl = el.querySelector('.spinner-overlay__message');
    if (msgEl && message) {
      msgEl.textContent = message;
    }
    el.removeAttribute('hidden');
    el.setAttribute('aria-busy', 'true');
  };

  window.hideSpinner = function (id) {
    const el = getSpinner(id);
    if (!el) return;
    el.setAttribute('hidden', '');
    el.setAttribute('aria-busy', 'false');
  };
})();

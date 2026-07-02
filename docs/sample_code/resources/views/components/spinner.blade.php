@props([
    'id' => 'spinner-overlay',
    'defaultMessage' => '処理中です。しばらくお待ちください...',
])

<div id="{{ $id }}" class="spinner-overlay" role="status" aria-live="polite" aria-busy="false" hidden>
  <div class="spinner-overlay__backdrop"></div>
  <div class="spinner-overlay__content">
    <div class="spinner" aria-hidden="true"></div>
    <p class="spinner-overlay__message">{{ $defaultMessage }}</p>
  </div>
</div>

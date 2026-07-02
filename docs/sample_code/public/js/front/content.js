// チェックしたときに入力無効にする
function change(checkboxId,disabledId) {
  var element;
  if(document.getElementById(checkboxId).checked) {
      element = document.getElementById(disabledId);
      element.disabled = true;
  }else {
      element = document.getElementById(disabledId);
      element.disabled = false;
  } 
  }
// チェックを入れたときに入力
function inputValue(checkbox, inputId, value) {
  const amountInput = document.getElementById(inputId);
  if (checkbox.checked) {
    amountInput.value = typeof value === 'number' ? value.toLocaleString() : String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (typeof formatAmountInput === 'function') {
      formatAmountInput(amountInput);
    }
  } else {
    amountInput.value = '';
  }
}

// 金額入力：数字のみ残し、3桁コンマで表示（整数のみ）
function formatAmountInput(input) {
  if (!input || typeof input.value === 'undefined') return;
  if (input.dataset.amountFormatting === '1') return;
  const raw = input.value.replace(/[^0-9]/g, '');
  const cursorPos = input.selectionStart;
  const oldLen = input.value.length;
  const newVal = raw === '' ? '' : parseInt(raw, 10).toLocaleString();
  if (input.value === newVal) return;
  input.dataset.amountFormatting = '1';
  input.value = newVal;
  const newLen = input.value.length;
  const newCursor = Math.max(0, cursorPos + (newLen - oldLen));
  input.setSelectionRange(newCursor, newCursor);
  delete input.dataset.amountFormatting;
}
// 画像アップロード時のプレビュー
function imgPreView(event,previewId,previewImageId){
  let file = event.target.files[0];
  let reader = new FileReader();
  let preview = document.getElementById(previewId);
  let previewImage = document.getElementById(previewImageId);  
  if(previewImage != null)
    preview.removeChild(previewImage);
  reader.onload = function(event) {
    let img = document.createElement("img");
    img.setAttribute("src", reader.result);
    img.setAttribute("id", previewImageId);
    preview.appendChild(img);    
  };
  reader.readAsDataURL(file);
}

// 削除時の警告表示
function deleteConfirm(record){
  if(confirm(record+"を削除してもいいですか？")){
    return true;
  }
  return false;
}
// 警告表示
function alertUser(message){
  if(confirm(message)){
    return true;
  }
  return false;
}
// 送信ボタン二重押し防止
function preventDoubleSubmit(form) {
  if (!form) return;
  form.addEventListener('submit', function () {
    var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    buttons.forEach(function (btn) {
      btn.disabled = true;
    });
  });
}
// 法人関連部分表示の切り替え
let memberCompanyTypeIndividual = document.getElementById("company-type-individual"); 
let memberCompanyTypeCorporate = document.getElementById("company-type-corporate");
let companyMatterElements = document.getElementsByClassName("company-matter");
function toggleCompanyMatter() {
  if (!memberCompanyTypeIndividual || !memberCompanyTypeCorporate) return;
  // 個人: 法人向け入力を非表示、法人: 表示
  if (memberCompanyTypeIndividual.checked) {
    for (let i = 0; i < companyMatterElements.length; i++) {
      companyMatterElements[i].classList.add('hidden');
    }
  } else {
    for (let i = 0; i < companyMatterElements.length; i++) {
      companyMatterElements[i].classList.remove('hidden');
    }
  }
}
if (memberCompanyTypeIndividual && memberCompanyTypeCorporate) {
  window.addEventListener('load', toggleCompanyMatter);
  memberCompanyTypeIndividual.addEventListener('change', toggleCompanyMatter);
  memberCompanyTypeCorporate.addEventListener('change', toggleCompanyMatter);
}

// 消費税税込み自動計算（コンマ付き value 対応）
function calculateTaxIncluded(input, outputId, taxRate) {
  const value = input.value.trim().replace(/,/g, '');
  const price = parseFloat(value);
  // 数値でない場合（空、null、文字、NaNなど）
  if (value === "" || isNaN(price)) {
    document.getElementById(outputId).innerText = "非売";
    return; }
  // 数値の場合 → 税込価格を表示
  const taxIncludedPrice = Math.round(price * (1 + taxRate));
  document.getElementById(outputId).innerText = 
    `税込 ${taxIncludedPrice.toLocaleString()} 円`;
}

document.addEventListener('DOMContentLoaded', function() {
  // --- ヘルプ（?ボタンで説明表示。クリックでトグル、外側クリックで閉じる）
  document.querySelectorAll('.help__trigger').forEach(function(trigger) {
    var wrapper = trigger.closest('.help');
    var content = wrapper ? wrapper.querySelector('.help__content') : null;
    if (!content) return;
    function open() {
      content.classList.add('help__content--open');
      content.removeAttribute('hidden');
      trigger.setAttribute('aria-expanded', 'true');
    }
    function close() {
      content.classList.remove('help__content--open');
      content.setAttribute('hidden', '');
      trigger.setAttribute('aria-expanded', 'false');
    }
    function toggle() {
      content.classList.contains('help__content--open') ? close() : open();
    }
    trigger.addEventListener('click', function(e) {
      e.stopPropagation();
      toggle();
    });
    document.addEventListener('click', function(e) {
      if (!wrapper.contains(e.target)) close();
    });
  });

  // --- 金額入力：.amount-input にコンマ表示・英字削除を一括適用
  document.querySelectorAll('.amount-input').forEach(function(el) {
    el.addEventListener('input', function() {
      formatAmountInput(this);
    });
    if (el.value && el.value.replace(/,/g, '').replace(/[^0-9]/g, '') !== '') {
      formatAmountInput(el);
    }
  });

  // --- 送信ボタン二重押し防止（共通）
  document.querySelectorAll('form.js-disable-on-submit').forEach(function(form) {
    preventDoubleSubmit(form);
  });

  // --- 文字数表示（汎用）.js-char-count 内の textarea と .char-count を紐付ける
  document.querySelectorAll('.js-char-count').forEach(function(container) {
    var textarea = container.querySelector('textarea');
    var counter = container.querySelector('.char-count');
    if (!textarea || !counter) return;
    var max = parseInt(textarea.getAttribute('maxlength'), 10) || 1000;
    function updateCount() {
      var n = textarea.value.length;
      counter.textContent = n + ' / ' + max + ' 文字';
    }
    textarea.addEventListener('input', updateCount);
    textarea.addEventListener('change', updateCount);
    updateCount();
  });
});

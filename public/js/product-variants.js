(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var chip = event.target.closest('[data-variant-chip]');
    if (!chip || chip.disabled) return;

    var card = chip.closest('.product-card, .product');
    if (!card) return;

    card.querySelectorAll('[data-variant-chip]').forEach(function (btn) {
      btn.classList.remove('is-active', 'active');
    });
    chip.classList.add(card.classList.contains('product-card') ? 'is-active' : 'active');

    var variantInput = card.querySelector('input[name="variant_id"]');
    if (variantInput) variantInput.value = chip.getAttribute('data-id');

    var priceEl = card.querySelector('[data-price]');
    if (priceEl) priceEl.textContent = chip.getAttribute('data-price');
  });
})();

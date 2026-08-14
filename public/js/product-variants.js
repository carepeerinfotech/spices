(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var chip = event.target.closest('[data-variant-chip]');
    if (!chip || chip.disabled) return;

    var card = chip.closest('.product-card, .product');
    if (!card) return;

    var isProductCard = card.classList.contains('product-card');
    var activeClasses = ['is-active', 'border-brand', 'bg-cream', 'text-brand'];
    var inactiveClasses = ['border-[var(--line)]', 'text-stone-600'];

    card.querySelectorAll('[data-variant-chip]').forEach(function (btn) {
      if (isProductCard) {
        btn.classList.remove.apply(btn.classList, activeClasses);
        btn.classList.add.apply(btn.classList, inactiveClasses);
      } else {
        btn.classList.remove('active');
      }
    });

    if (isProductCard) {
      chip.classList.remove.apply(chip.classList, inactiveClasses);
      chip.classList.add.apply(chip.classList, activeClasses);
    } else {
      chip.classList.add('active');
    }

    var variantInput = card.querySelector('input[name="variant_id"]');
    if (variantInput) variantInput.value = chip.getAttribute('data-id');

    var priceEl = card.querySelector('[data-price]');
    if (priceEl) priceEl.textContent = chip.getAttribute('data-price');
  });
})();

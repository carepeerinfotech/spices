(function () {
  'use strict';

  var drawer = document.getElementById('cart-drawer');
  if (!drawer) return;

  var itemsRoot = drawer.querySelector('[data-cart-drawer-items]');
  var subtotalEl = drawer.querySelector('[data-cart-drawer-subtotal]');
  var closers = drawer.querySelectorAll('[data-cart-drawer-close]');

  function money(n) {
    return '₹' + Number(n).toFixed(2);
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function open() {
    drawer.classList.add('is-open');
    document.body.classList.add('cart-drawer-open');
  }

  function close() {
    drawer.classList.remove('is-open');
    document.body.classList.remove('cart-drawer-open');
  }

  function render(summary) {
    if (window.AppAjax) window.AppAjax.updateCartBadge(summary.item_count);
    subtotalEl.textContent = money(summary.subtotal);

    if (!summary.items || !summary.items.length) {
      itemsRoot.innerHTML = '<p class="cart-drawer__empty">Your cart is empty.</p>';
      return;
    }

    itemsRoot.innerHTML = summary.items.map(function (item) {
      return (
        '<div class="cart-drawer__item" data-item-id="' + item.id + '">' +
        (item.image ? '<img src="' + item.image + '" alt="">' : '<div class="cart-drawer__item-noimg"></div>') +
        '<div class="cart-drawer__item-body">' +
        '<p class="cart-drawer__item-name">' + escapeHtml(item.name) + '</p>' +
        (item.variant_label ? '<p class="cart-drawer__item-variant">' + escapeHtml(item.variant_label) + '</p>' : '') +
        '<p class="cart-drawer__item-meta">Qty ' + item.quantity + ' &times; ' + money(item.price) + '</p>' +
        '</div>' +
        '<button type="button" class="cart-drawer__item-remove" data-cart-drawer-remove="' + item.id + '" aria-label="Remove item">&times;</button>' +
        '</div>'
      );
    }).join('');
  }

  function removeItem(id) {
    window.AppAjax.request('/cart/' + id, { method: 'DELETE' }).then(function (result) {
      if (result.ok) render(result.data.data);
    });
  }

  closers.forEach(function (el) {
    el.addEventListener('click', close);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawer.classList.contains('is-open')) close();
  });

  itemsRoot.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-cart-drawer-remove]');
    if (!btn) return;
    removeItem(btn.getAttribute('data-cart-drawer-remove'));
  });

  document.addEventListener('ajax:done', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if ((form.getAttribute('method') || '').toUpperCase() !== 'POST') return;

    var path = new URL(form.action, window.location.origin).pathname;
    if (path !== '/cart') return;

    var detail = event.detail;
    if (!detail || !detail.ok || !detail.data || !detail.data.data) return;

    render(detail.data.data);
    open();
  });

  window.CartDrawer = { open: open, close: close, render: render };
})();

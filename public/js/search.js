(function () {
  'use strict';

  var toggle = document.querySelector('[data-search-toggle]');
  var modal = document.getElementById('search-modal');
  if (!toggle || !modal) return;

  var input = modal.querySelector('[data-search-input]');
  var results = modal.querySelector('[data-search-results]');
  var form = modal.querySelector('.search-modal__form');
  var closers = modal.querySelectorAll('[data-search-close]');
  var debounceTimer = null;
  var currentRequest = null;

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('search-open');
    toggle.setAttribute('aria-expanded', 'true');
    window.setTimeout(function () { input.focus(); }, 0);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('search-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.focus();
  }

  function renderHint(message) {
    results.innerHTML = '<p class="search-modal__hint">' + escapeHtml(message) + '</p>';
  }

  function renderResults(products, term) {
    if (!products.length) {
      renderHint('No products found for "' + term + '".');
      return;
    }

    var html = products.map(function (product) {
      return (
        '<a class="search-result" href="' + product.url + '">' +
        (product.image ? '<img src="' + product.image + '" alt="">' : '') +
        '<span><span class="search-result__name">' + escapeHtml(product.name) + '</span><br>' +
        '<span class="search-result__price">' + escapeHtml(product.price) + '</span></span>' +
        '</a>'
      );
    }).join('');

    html += '<a class="search-modal__viewall" href="' + form.action + '?q=' + encodeURIComponent(term) + '">View all results for "' + escapeHtml(term) + '"</a>';

    results.innerHTML = html;
  }

  function search(term) {
    if (currentRequest) currentRequest.abort();

    var controller = window.AbortController ? new AbortController() : null;
    currentRequest = controller;

    fetch('/search/suggest?q=' + encodeURIComponent(term), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller ? controller.signal : undefined,
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        currentRequest = null;
        renderResults(data.products || [], term);
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') return;
        currentRequest = null;
        renderHint('Something went wrong. Please try again.');
      });
  }

  toggle.addEventListener('click', openModal);

  closers.forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  input.addEventListener('input', function () {
    var term = input.value.trim();
    window.clearTimeout(debounceTimer);

    if (term.length < 2) {
      renderHint('Type at least 2 characters to search.');
      return;
    }

    debounceTimer = window.setTimeout(function () { search(term); }, 300);
  });

  form.addEventListener('submit', function (event) {
    if (!input.value.trim()) event.preventDefault();
  });

  renderHint('Type at least 2 characters to search.');
})();

/**
 * Transparent header on homepage + mobile nav toggle.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var header = document.querySelector('[data-transparent-header]');
    if (header) {
      var ticking = false;

      function updateHeader() {
        header.classList.toggle('is-scrolled', window.scrollY > 24);
        ticking = false;
      }

      function onScroll() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateHeader);
      }

      updateHeader();
      window.addEventListener('scroll', onScroll, { passive: true });
    }

    var mobileHeader = document.querySelector('[data-mobile-header]');
    var toggle = document.querySelector('[data-menu-toggle]');
    if (mobileHeader && toggle) {
      toggle.addEventListener('click', function () {
        var open = mobileHeader.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
  });
})();

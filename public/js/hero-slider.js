/**
 * Homepage hero image slider (spice split layout).
 */
(function () {
  'use strict';

  function initHero(root) {
    var slides = Array.prototype.slice.call(root.querySelectorAll('.spice-hero__slide'));
    if (slides.length < 2) return;

    var dots = Array.prototype.slice.call(root.querySelectorAll('[data-slider-dot]'));
    var prev = root.querySelector('[data-slider-prev]');
    var next = root.querySelector('[data-slider-next]');
    var index = 0;
    var timer = null;
    var interval = 5200;
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function goTo(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        var active = n === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });
      dots.forEach(function (dot, n) {
        var active = n === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    function start() {
      if (reduced) return;
      stop();
      timer = window.setInterval(function () { goTo(index + 1); }, interval);
    }

    function stop() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    if (prev) prev.addEventListener('click', function () { goTo(index - 1); start(); });
    if (next) next.addEventListener('click', function () { goTo(index + 1); start(); });
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        goTo(parseInt(dot.getAttribute('data-slider-dot'), 10) || 0);
        start();
      });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop();
      else start();
    });

    start();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-hero-slider]').forEach(initHero);
  });
})();

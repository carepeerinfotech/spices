/**
 * Behaviour for <x-image-upload>: choosing files (click or drag), previewing
 * them before save, and viewing any image full size.
 *
 * Removing a saved image is handled by the shared [data-delete] binding in
 * app-ajax.js, so nothing here duplicates it.
 */
(function (window, document) {
  'use strict';

  var PENDING_URLS = 'imageUploadUrls';

  function toArray(list) {
    return list ? Array.prototype.slice.call(list) : [];
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  function humanSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  // ---------------------------------------------------------------- lightbox

  var overlay = null;

  function buildOverlay() {
    overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/80 p-6';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = [
      '<button type="button" data-image-lightbox-close aria-label="Close preview"',
      ' class="absolute right-5 top-4 text-3xl leading-none text-white/80 hover:text-white">&times;</button>',
      '<figure class="max-h-full max-w-4xl text-center">',
      '<img data-image-lightbox-img src="" alt=""',
      ' class="mx-auto max-h-[80vh] w-auto max-w-full rounded-lg bg-white object-contain shadow-2xl">',
      '<figcaption data-image-lightbox-caption class="mt-3 text-sm text-white/70"></figcaption>',
      '</figure>'
    ].join('');

    overlay.addEventListener('click', function (event) {
      // Close on the backdrop or the close button, never on the image itself.
      if (event.target === overlay || event.target.closest('[data-image-lightbox-close]')) {
        closeLightbox();
      }
    });

    document.body.appendChild(overlay);
  }

  function openLightbox(src, caption) {
    if (!overlay) buildOverlay();

    overlay.querySelector('[data-image-lightbox-img]').src = src;
    overlay.querySelector('[data-image-lightbox-caption]').textContent = caption || '';
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!overlay) return;

    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    overlay.querySelector('[data-image-lightbox-img]').src = '';
    document.body.style.overflow = '';
  }

  // ------------------------------------------------------- pending previews

  function releaseUrls(field) {
    (field[PENDING_URLS] || []).forEach(function (url) {
      URL.revokeObjectURL(url);
    });
    field[PENDING_URLS] = [];
  }

  function renderPending(field) {
    var input = field.querySelector('[data-image-upload-input]');
    var target = field.querySelector('[data-image-upload-pending]');
    if (!input || !target) return;

    releaseUrls(field);
    target.innerHTML = '';

    var files = toArray(input.files).filter(function (file) {
      return file.type.indexOf('image/') === 0;
    });

    target.hidden = files.length === 0;
    if (!files.length) return;

    files.forEach(function (file, index) {
      var url = URL.createObjectURL(file);
      field[PENDING_URLS].push(url);

      var card = document.createElement('figure');
      card.className = 'w-28 overflow-hidden rounded-lg border border-teal-300 bg-white ring-1 ring-teal-200';
      card.innerHTML = [
        '<button type="button" data-image-preview="' + url + '" data-image-caption="' + escapeHtml(file.name) + '"',
        ' title="Preview full size" class="block h-24 w-full cursor-zoom-in bg-slate-50">',
        '<img src="' + url + '" alt="" class="h-full w-full object-cover">',
        '</button>',
        '<figcaption class="border-t border-teal-200 px-1.5 py-1 text-center">',
        '<span class="block truncate text-[11px] text-slate-600" title="' + escapeHtml(file.name) + '">',
        escapeHtml(file.name),
        '</span>',
        '<span class="block text-[10px] text-slate-400">' + humanSize(file.size) + '</span>',
        '<button type="button" data-image-upload-remove="' + index + '"',
        ' class="mt-0.5 text-[11px] text-rose-600 hover:underline">Remove</button>',
        '</figcaption>'
      ].join('');

      target.appendChild(card);
    });
  }

  /** Drop a single file from the input's selection without clearing the rest. */
  function removePendingFile(field, index) {
    var input = field.querySelector('[data-image-upload-input]');
    if (!input) return;

    if (typeof DataTransfer === 'undefined') {
      input.value = '';
    } else {
      var transfer = new DataTransfer();
      toArray(input.files).forEach(function (file, i) {
        if (i !== index) transfer.items.add(file);
      });
      input.files = transfer.files;
    }

    renderPending(field);
  }

  function assignFiles(field, fileList) {
    var input = field.querySelector('[data-image-upload-input]');
    if (!input || !fileList || !fileList.length) return;

    var images = toArray(fileList).filter(function (file) {
      return file.type.indexOf('image/') === 0;
    });
    if (!images.length) return;

    if (typeof DataTransfer === 'undefined') return;

    var transfer = new DataTransfer();
    // A single-image collection keeps only the last file dropped.
    (input.multiple ? images : images.slice(0, 1)).forEach(function (file) {
      transfer.items.add(file);
    });

    input.files = transfer.files;
    renderPending(field);
  }

  // ------------------------------------------------------- primary & sorting

  /** Repaint the field so exactly one thumbnail reads as primary. */
  function markPrimary(button) {
    var field = button.closest('[data-image-upload]');
    var chosen = button.closest('figure[data-image-id]');
    if (!field || !chosen) return;

    toArray(field.querySelectorAll('figure[data-image-id]')).forEach(function (figure) {
      var isChosen = figure === chosen;

      figure.classList.toggle('border-teal-500', isChosen);
      figure.classList.toggle('ring-2', isChosen);
      figure.classList.toggle('ring-teal-200', isChosen);
      figure.classList.toggle('border-slate-200', !isChosen);

      var star = figure.querySelector('[data-image-primary]');
      if (!star) return;

      star.setAttribute('aria-pressed', isChosen ? 'true' : 'false');
      star.classList.toggle('text-amber-500', isChosen);
      star.title = isChosen ? 'Primary image' : 'Make primary';

      var icon = star.querySelector('svg');
      if (icon) icon.setAttribute('fill', isChosen ? 'currentColor' : 'none');
    });
  }

  function persistOrder(container) {
    var url = container.getAttribute('data-image-reorder-url');
    if (!url || !window.AppAjax) return;

    var ids = toArray(container.querySelectorAll('figure[data-image-id]')).map(function (figure) {
      return parseInt(figure.getAttribute('data-image-id'), 10);
    });

    window.AppAjax.request(url, { method: 'POST', body: { ids: ids } });
  }

  function bindSortable(container) {
    if (!container || container.dataset.sortableBound) return;
    container.dataset.sortableBound = '1';

    var dragged = null;

    container.addEventListener('dragstart', function (event) {
      var figure = event.target.closest('figure[data-image-id]');
      if (!figure) return;

      dragged = figure;
      event.dataTransfer.effectAllowed = 'move';
      // Firefox refuses to start a drag unless some data is attached.
      try {
        event.dataTransfer.setData('text/plain', figure.getAttribute('data-image-id'));
      } catch (ignored) {}
      figure.classList.add('opacity-40');
    });

    container.addEventListener('dragend', function () {
      if (dragged) dragged.classList.remove('opacity-40');
      dragged = null;
    });

    container.addEventListener('dragover', function (event) {
      if (!dragged) return;
      event.preventDefault();

      var over = event.target.closest('figure[data-image-id]');
      if (!over || over === dragged) return;

      // Drop after the hovered thumbnail once past its midpoint.
      var box = over.getBoundingClientRect();
      var after = event.clientX - box.left > box.width / 2;
      container.insertBefore(dragged, after ? over.nextSibling : over);
    });

    container.addEventListener('drop', function (event) {
      if (!dragged) return;
      event.preventDefault();
      persistOrder(container);
    });
  }

  // -------------------------------------------------------------- listeners

  /**
   * app-ajax.js removes a deleted thumbnail from the DOM without telling us,
   * so watch the gallery and bring the empty-state text back when it drains.
   */
  function watchSaved(field) {
    var saved = field.querySelector('[data-image-upload-saved]');
    var empty = field.querySelector('[data-image-upload-empty]');
    if (!saved || !empty || typeof MutationObserver === 'undefined') return;

    var sync = function () {
      var count = saved.querySelectorAll('figure').length;
      saved.hidden = count === 0;
      empty.hidden = count > 0;
    };

    new MutationObserver(sync).observe(saved, { childList: true });
    sync();
  }

  function bindFields() {
    toArray(document.querySelectorAll('[data-image-upload]')).forEach(function (field) {
      var zone = field.querySelector('[data-image-upload-dropzone]');
      if (!zone || zone.dataset.imageUploadBound) return;
      zone.dataset.imageUploadBound = '1';

      watchSaved(field);
      bindSortable(field.querySelector('[data-image-upload-sortable]'));

      ['dragenter', 'dragover'].forEach(function (name) {
        zone.addEventListener(name, function (event) {
          event.preventDefault();
          zone.classList.add('border-teal-500', 'bg-teal-50');
        });
      });

      ['dragleave', 'drop'].forEach(function (name) {
        zone.addEventListener(name, function (event) {
          event.preventDefault();
          zone.classList.remove('border-teal-500', 'bg-teal-50');
        });
      });

      zone.addEventListener('drop', function (event) {
        assignFiles(field, event.dataTransfer && event.dataTransfer.files);
      });
    });
  }

  document.addEventListener('change', function (event) {
    var input = event.target.closest('[data-image-upload-input]');
    if (!input) return;

    renderPending(input.closest('[data-image-upload]'));
  });

  document.addEventListener('click', function (event) {
    var preview = event.target.closest('[data-image-preview]');
    if (preview) {
      event.preventDefault();
      openLightbox(
        preview.getAttribute('data-image-preview'),
        preview.getAttribute('data-image-caption')
      );

      return;
    }

    var star = event.target.closest('[data-image-primary]');
    if (star) {
      event.preventDefault();
      if (star.getAttribute('aria-pressed') === 'true' || !window.AppAjax) return;

      window.AppAjax
        .request(star.getAttribute('data-image-primary'), { method: 'POST' })
        .then(function (result) {
          if (result.ok) markPrimary(star);
        });

      return;
    }

    var remove = event.target.closest('[data-image-upload-remove]');
    if (remove) {
      event.preventDefault();
      removePendingFile(
        remove.closest('[data-image-upload]'),
        parseInt(remove.getAttribute('data-image-upload-remove'), 10)
      );
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeLightbox();
  });

  document.addEventListener('DOMContentLoaded', bindFields);

  window.ImageUpload = { open: openLightbox, close: closeLightbox, refresh: bindFields };
})(window, document);

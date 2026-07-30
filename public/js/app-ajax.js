/**
 * Lightweight AJAX helper — no dependencies.
 * Uses fetch + CSRF, returns JSON, surfaces errors cleanly.
 */
(function (window, document) {
  'use strict';

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function toast(message, type) {
    var el = document.getElementById('toast-root');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast-root';
      el.className = 'toast-root';
      document.body.appendChild(el);
    }

    var item = document.createElement('div');
    item.className = 'toast toast-' + (type || 'info');
    item.textContent = message;
    el.appendChild(item);

    requestAnimationFrame(function () {
      item.classList.add('show');
    });

    setTimeout(function () {
      item.classList.remove('show');
      setTimeout(function () {
        item.remove();
      }, 250);
    }, 3200);
  }

  function parseJsonSafe(text) {
    if (!text) return {};
    try {
      return JSON.parse(text);
    } catch (e) {
      return { success: false, message: 'Invalid server response.' };
    }
  }

  async function ajax(url, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    var headers = Object.assign(
      {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      options.headers || {}
    );

    var body = options.body;
    if (body && !(body instanceof FormData) && typeof body === 'object') {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }

    var response;
    try {
      response = await fetch(url, {
        method: method,
        headers: headers,
        body: method === 'GET' || method === 'HEAD' ? undefined : body,
        credentials: 'same-origin',
      });
    } catch (networkError) {
      var netMsg = 'Network error. Please try again.';
      if (options.toast !== false) toast(netMsg, 'error');
      return { ok: false, status: 0, data: { success: false, message: netMsg } };
    }

    var text = await response.text();
    var data = parseJsonSafe(text);

    if (!response.ok) {
      var message =
        data.message ||
        (data.errors && Object.values(data.errors).flat()[0]) ||
        'Request failed.';
      if (options.toast !== false) toast(message, 'error');
      return { ok: false, status: response.status, data: data };
    }

    if (data.success === false) {
      if (options.toast !== false) toast(data.message || 'Request failed.', 'error');
      return { ok: false, status: response.status, data: data };
    }

    if (options.toast !== false && data.message) {
      toast(data.message, 'success');
    }

    if (data.redirect) {
      setTimeout(function () {
        window.location.href = data.redirect;
      }, options.redirectDelay || 400);
    }

    return { ok: true, status: response.status, data: data };
  }

  function parseName(name) {
    var keys = [];
    var re = /([^\[\]]+)|\[([^\]]*)\]/g;
    var match;
    while ((match = re.exec(name))) {
      keys.push(match[1] !== undefined ? match[1] : match[2]);
    }
    return keys;
  }

  function setDeep(obj, keys, value, overwrite) {
    var cursor = obj;
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      var last = i === keys.length - 1;

      if (last) {
        if (key === '') {
          if (!Array.isArray(cursor)) return;
          cursor.push(value);
        } else if (!overwrite && Object.prototype.hasOwnProperty.call(cursor, key)) {
          if (!Array.isArray(cursor[key])) cursor[key] = [cursor[key]];
          cursor[key].push(value);
        } else {
          cursor[key] = value;
        }
        return;
      }

      var next = keys[i + 1];
      if (key === '') {
        if (!Array.isArray(cursor)) return;
        var child = next === '' || /^\d+$/.test(next) ? [] : {};
        cursor.push(child);
        cursor = child;
        continue;
      }

      if (!Object.prototype.hasOwnProperty.call(cursor, key) || cursor[key] === null || typeof cursor[key] !== 'object') {
        cursor[key] = next === '' || /^\d+$/.test(String(next)) ? [] : {};
      }
      cursor = cursor[key];
    }
  }

  function formToObject(form) {
    var fd = new FormData(form);
    var obj = {};

    // Names of single-value boolean checkboxes; these are authoritative and
    // must never be merged with their own FormData "on/1" value.
    var boolNames = {};
    form.querySelectorAll('input[type="checkbox"][data-bool]').forEach(function (cb) {
      boolNames[cb.name] = true;
    });

    fd.forEach(function (value, key) {
      if (boolNames[key]) return; // handled explicitly below
      setDeep(obj, parseName(key), value);
    });

    form.querySelectorAll('input[type="checkbox"][data-bool]').forEach(function (cb) {
      setDeep(obj, parseName(cb.name), cb.checked ? 1 : 0, true);
    });

    return obj;
  }

  function bindAjaxForms() {
    document.addEventListener('submit', function (e) {
      var form = e.target.closest('form[data-ajax]');
      if (!form) return;
      e.preventDefault();

      var btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.textContent = btn.dataset.loading || 'Saving...';
      }

      var method = (form.getAttribute('method') || 'POST').toUpperCase();
      var useJson = form.dataset.ajax !== 'formdata';
      var body = useJson ? formToObject(form) : new FormData(form);

      if (!(body instanceof FormData) && form.querySelector('input[name="_method"]')) {
        method = form.querySelector('input[name="_method"]').value.toUpperCase();
      }

      ajax(form.action, { method: method, body: body })
        .then(function (result) {
          form.dispatchEvent(new CustomEvent('ajax:done', { detail: result, bubbles: true }));
        })
        .finally(function () {
          if (btn) {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || 'Save';
          }
        });
    });
  }

  function bindAjaxDeletes() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-delete]');
      if (!btn) return;
      e.preventDefault();

      var url = btn.getAttribute('data-delete');
      var confirmMsg = btn.getAttribute('data-confirm') || 'Delete this item?';
      if (!window.confirm(confirmMsg)) return;

      ajax(url, { method: 'DELETE' }).then(function (result) {
        if (!result.ok) return;
        var row = btn.closest('tr, .js-deletable');
        if (row) {
          row.style.transition = 'opacity .2s';
          row.style.opacity = '0';
          setTimeout(function () {
            row.remove();
          }, 200);
        } else {
          window.location.reload();
        }
      });
    });
  }

  function updateCartBadge(count) {
    document.querySelectorAll('[data-cart-count]').forEach(function (el) {
      el.textContent = String(count || 0);
      el.hidden = !count;
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindAjaxForms();
    bindAjaxDeletes();
  });

  window.AppAjax = {
    request: ajax,
    toast: toast,
    formToObject: formToObject,
    updateCartBadge: updateCartBadge,
  };
})(window, document);

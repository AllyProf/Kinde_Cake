(function () {
  'use strict';

  var loader = document.getElementById('appPageLoader');
  if (!loader) {
    return;
  }

  var loadStartedAt = Date.now();
  var minVisibleMs = 450;
  var hideTimer = null;

  function hideLoader() {
    if (hideTimer) {
      return;
    }

    var elapsed = Date.now() - loadStartedAt;
    var delay = Math.max(0, minVisibleMs - elapsed);

    hideTimer = window.setTimeout(function () {
      loader.classList.add('is-done');
      loader.setAttribute('aria-busy', 'false');
    }, delay);
  }

  function showLoader() {
    if (hideTimer) {
      window.clearTimeout(hideTimer);
      hideTimer = null;
    }

    loadStartedAt = Date.now();
    loader.classList.remove('is-done');
    loader.setAttribute('aria-busy', 'true');
  }

  window.appShowPageLoader = showLoader;
  window.appHidePageLoader = hideLoader;

  window.addEventListener('load', hideLoader);
  window.setTimeout(hideLoader, 8000);

  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href]');
    if (!link) {
      return;
    }

    var href = link.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
      return;
    }

    if (link.getAttribute('data-toggle') === 'treeview'
      || link.getAttribute('data-toggle') === 'sidebar'
      || link.getAttribute('data-toggle') === 'dropdown'
      || link.getAttribute('data-toggle') === 'modal'
      || link.getAttribute('data-toggle') === 'collapse') {
      return;
    }

    if (link.getAttribute('data-no-loader') !== null) {
      return;
    }

    if (link.target === '_blank' || link.hasAttribute('download')) {
      return;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    try {
      var url = new URL(link.href, window.location.origin);
      if (url.origin !== window.location.origin) {
        return;
      }
      if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return;
      }
    } catch (error) {
      return;
    }

    showLoader();
  }, true);

  document.addEventListener('submit', function (event) {
    var form = event.target;

    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    if (event.defaultPrevented) {
      return;
    }

    if (form.getAttribute('data-no-loader') !== null) {
      return;
    }

    if (form.target === '_blank') {
      return;
    }

    showLoader();
  });

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      hideLoader();
    }
  });
})();

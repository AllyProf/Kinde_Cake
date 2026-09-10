(function () {
  'use strict';

  var SKIP_SELECTOR = [
    '[data-no-loader]',
    '[data-toggle="modal"]',
    '[data-toggle="dropdown"]',
    '[data-toggle="collapse"]',
    '[data-toggle="treeview"]',
    '[data-toggle="sidebar"]',
    '[data-dismiss="modal"]',
    '.js-toggle-password',
    '.js-generate-password',
    '.js-reset-password',
    '.close',
  ].join(',');

  function shouldSkipButton(button) {
    if (!button || button.disabled || button.classList.contains('is-loading')) {
      return true;
    }

    if (button.matches(SKIP_SELECTOR)) {
      return true;
    }

    if (button.closest(SKIP_SELECTOR)) {
      return true;
    }

    // Non-submit buttons (Remove, Add row, modal actions, etc.) must not show the loader.
    if (button.type === 'button') {
      return true;
    }

    return false;
  }

  function loadingText(button) {
    return button.getAttribute('data-loading-text')
      || button.getAttribute('aria-label')
      || 'Please wait...';
  }

  function setButtonLoading(button) {
    if (!button || shouldSkipButton(button)) {
      return;
    }

    button.classList.add('is-loading');
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    if (button.tagName === 'INPUT') {
      if (!button.dataset.originalValue) {
        button.dataset.originalValue = button.value;
      }
      button.value = loadingText(button);
      return;
    }

    if (!button.dataset.originalHtml) {
      button.dataset.originalHtml = button.innerHTML;
    }

    button.innerHTML =
      '<span class="app-btn-spinner" aria-hidden="true"><i class="fa fa-spinner fa-spin"></i></span>' +
      '<span class="app-btn-label">' + loadingText(button) + '</span>';
  }

  window.appSetButtonLoading = setButtonLoading;

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

    if (form.classList.contains('js-swal-delete') || form.classList.contains('js-swal-confirm')) {
      return;
    }

    var submitter = event.submitter
      || form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');

    setButtonLoading(submitter);
  }, true);

  document.addEventListener('click', function (event) {
    var button = event.target.closest('button, input[type="submit"]');

    if (!button || shouldSkipButton(button)) {
      return;
    }

    var form = button.form
      || (button.getAttribute('form') ? document.getElementById(button.getAttribute('form')) : null);

    if (form && (form.classList.contains('js-swal-delete') || form.classList.contains('js-swal-confirm'))) {
      return;
    }

    if (button.type === 'submit' && form) {
      return;
    }

    if (form && form.getAttribute('data-no-loader') === null) {
      setButtonLoading(button);
    }
  }, true);
})();

(function ($) {
  'use strict';

  function brandConfirmColor() {
    var color = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim();

    return color || '#009688';
  }

  window.AppAlerts = {
    success: function (title, text) {
      swal({
        title: title || 'Success',
        text: text || '',
        type: 'success',
        confirmButtonColor: brandConfirmColor(),
      });
    },
    error: function (title, text) {
      swal({
        title: title || 'Error',
        text: text || '',
        type: 'error',
        confirmButtonColor: brandConfirmColor(),
      });
    },
    confirm: function (options, onConfirm) {
      swal({
        title: options.title || 'Are you sure?',
        text: options.text || '',
        type: options.type || 'warning',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Yes',
        cancelButtonText: options.cancelText || 'Cancel',
        confirmButtonColor: brandConfirmColor(),
        closeOnConfirm: true,
        closeOnCancel: true,
      }, function (isConfirm) {
        if (isConfirm && typeof onConfirm === 'function') {
          onConfirm();
        }
      });
    },
  };

  $(function () {
    $('.js-swal-delete').on('submit', function (event) {
      event.preventDefault();
      var $form = $(this);
      var $btn = $form.find('[type=submit]');

      AppAlerts.confirm({
        title: $btn.data('title') || 'Delete?',
        text: $btn.data('text') || 'This action cannot be undone.',
        confirmText: $btn.data('confirm') || 'Yes, delete it',
        cancelText: $btn.data('cancel') || 'Cancel',
      }, function () {
        var $submit = $form.find('[type=submit]');
        if (window.appSetButtonLoading && $submit.length) {
          window.appSetButtonLoading($submit[0]);
        }
        if (window.appShowPageLoader) {
          window.appShowPageLoader();
        }
        $form.off('submit').submit();
      });
    });

    $('.js-swal-confirm').on('submit', function (event) {
      event.preventDefault();
      var $form = $(this);
      var $btn = $form.find('[type=submit]');

      AppAlerts.confirm({
        title: $btn.data('title') || 'Are you sure?',
        text: $btn.data('text') || '',
        type: $btn.data('type') || 'warning',
        confirmText: $btn.data('confirm') || 'Yes',
        cancelText: $btn.data('cancel') || 'Cancel',
      }, function () {
        var $submit = $form.find('[type=submit]');
        if (window.appSetButtonLoading && $submit.length) {
          window.appSetButtonLoading($submit[0]);
        }
        if (window.appShowPageLoader) {
          window.appShowPageLoader();
        }
        $form.off('submit').submit();
      });
    });
  });
})(jQuery);

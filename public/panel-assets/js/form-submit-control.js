(function ($) {
  'use strict';

  window.FormSubmitControl = {
    setEnabled: function ($button, enabled) {
      if (!$button || !$button.length) {
        return;
      }

      $button.prop('disabled', !enabled);
    },

    watch: function (options) {
      var $form = $(options.form);
      var $button = $(options.button);
      var validate = options.validate;

      if (!$form.length || !$button.length || typeof validate !== 'function') {
        return;
      }

      function refresh() {
        FormSubmitControl.setEnabled($button, validate($form));
      }

      $form.on('input change', 'input, select, textarea', refresh);
      $(document).on('click', options.removeRowSelector || '.js-remove-row', function () {
        window.setTimeout(refresh, 0);
      });

      if (options.extraEvents) {
        options.extraEvents.on('change input select2:select select2:clear', refresh);
      }

      refresh();

      return refresh;
    },
  };
})(jQuery);

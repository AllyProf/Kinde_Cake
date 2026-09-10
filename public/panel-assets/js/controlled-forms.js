(function ($) {
  'use strict';

  function formIsValid($form) {
    var form = $form[0];

    if (form && typeof form.checkValidity === 'function') {
      return form.checkValidity();
    }

    var valid = true;

    $form.find('[required]').each(function () {
      if (!this.value || !String(this.value).trim()) {
        valid = false;
      }
    });

    return valid;
  }

  $(function () {
    if (!window.FormSubmitControl) {
      return;
    }

    $('.js-controlled-form').each(function () {
      var $form = $(this);
      var $button = $form.find('.js-controlled-submit');

      FormSubmitControl.watch({
        form: $form,
        button: $button,
        validate: function () {
          return formIsValid($form);
        },
      });
    });
  });
})(jQuery);

(function ($) {
  'use strict';

  function syncPhoneFull($local) {
    var target = $($local.data('phone-full'));
    if (!target.length) {
      return;
    }

    var digits = ($local.val() || '').replace(/\D/g, '').slice(0, 9);
    $local.val(digits);
    target.val(digits ? '+255' + digits : '');
  }

  function parsePhoneLocal(full) {
    if (!full) {
      return '';
    }

    return full.replace(/^\+255/, '').replace(/\D/g, '').slice(0, 9);
  }

  window.PhoneTz = {
    parsePhoneLocal: parsePhoneLocal,
    syncPhoneFull: syncPhoneFull,
    setPhone: function ($local, fullPhone) {
      $local.val(parsePhoneLocal(fullPhone));
      syncPhoneFull($local);
    },
  };

  $(function () {
    $(document).on('input', '.js-tz-phone-local', function () {
      syncPhoneFull($(this));
    });

    $('.js-tz-phone-local').each(function () {
      syncPhoneFull($(this));
    });
  });
})(jQuery);

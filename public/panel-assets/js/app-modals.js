(function ($) {
  'use strict';

  // Modals inside .app-content sit in a different stacking layer than the
  // backdrop Bootstrap appends to body — the dim overlay can look missing/transparent.
  $(document).on('show.bs.modal', '.modal', function () {
    var $modal = $(this);

    if ($modal.parent()[0] !== document.body) {
      $modal.appendTo('body');
    }

    $('.modal-backdrop').not('.show').remove();
  });

  $(document).on('hidden.bs.modal', '.modal', function () {
    if ($('.modal.show').length === 0) {
      $('.modal-backdrop').remove();
      $('body').removeClass('modal-open');
      $('body').css('padding-right', '');
    }
  });
})(jQuery);

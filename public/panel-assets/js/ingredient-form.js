(function ($) {
  'use strict';

  var units = window.ingredientPackageUnits || [];
  var unitMap = {};

  units.forEach(function (unit) {
    unitMap[String(unit.id)] = unit;
  });

  function selectedUnit(selectId) {
    return unitMap[$(selectId).val()] || null;
  }

  function formatQty(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
      return null;
    }

    return num % 1 === 0 ? String(num) : String(num);
  }

  function updateForm() {
    var receiving = selectedUnit('#receivingPackageSelect');
    var usage = selectedUnit('#usagePackageSelect');
    var qty = formatQty($('#usagePerReceivingInput').val());
    var $summary = $('#conversionSummary');

    if (receiving && usage) {
      $('#usagePerReceivingLabelText').text(usage.name + ' in 1 ' + receiving.name);
    } else if (receiving) {
      $('#usagePerReceivingLabelText').text('Qty in 1 ' + receiving.name);
    } else {
      $('#usagePerReceivingLabelText').text('Qty in 1 unit');
    }

    if (receiving && usage && qty) {
      $summary
        .addClass('is-ready')
        .text('1 ' + receiving.symbol + ' = ' + qty + ' ' + usage.symbol);
    } else {
      $summary.removeClass('is-ready').text('—');
    }
  }

  $(function () {
    $('#receivingPackageSelect, #usagePackageSelect, #usagePerReceivingInput').on('change input', updateForm);
    updateForm();
  });
})(jQuery);

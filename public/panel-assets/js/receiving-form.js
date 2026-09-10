(function ($) {
  'use strict';

  var options = window.ingredientReceivingOptions || [];
  var map = {};

  options.forEach(function (item) {
    map[String(item.id)] = item;
  });

  function currentMode() {
    return $('input[name="receive_mode"]:checked').val() || 'package';
  }

  function selectedIngredient() {
    return map[$('#ingredientSelect').val()] || null;
  }

  function formatQty(value) {
    var num = parseFloat(value);
    if (isNaN(num)) {
      return null;
    }

    return num % 1 === 0 ? String(num) : String(Number(num.toFixed(2)));
  }

  function formatStockLine(ingredient) {
    var usageQty = formatQty(ingredient.stock_quantity);
    var usageSym = ingredient.usage_symbol || '';

    return 'Available: <strong>' + usageQty + ' ' + usageSym + '</strong>';
  }

  function isReceivingValid() {
    var ingredient = selectedIngredient();
    var qty = parseFloat($('#quantityInput').val());
    var cost = parseFloat($('#purchaseCostInput').val());
    return !!ingredient && !isNaN(qty) && qty > 0 && !isNaN(cost) && cost >= 0;
  }

  function updateForm() {
    var ingredient = selectedIngredient();
    var mode = currentMode();
    var qty = parseFloat($('#quantityInput').val());
    var $summary = $('#stockPreview');

    if (!ingredient) {
      $('#quantityUnitLabel').text('—');
      $summary.removeClass('is-ready').addClass('is-empty')
        .text('Select an ingredient to see current stock.');
    } else if (mode === 'usage') {
      $('#quantityUnitLabel').text(ingredient.usage_symbol || '—');
    } else {
      $('#quantityUnitLabel').text(ingredient.receiving_symbol || '—');
    }

    if (ingredient) {
      $summary.removeClass('is-empty').addClass('is-ready').html(formatStockLine(ingredient));
    }

    if (window.FormSubmitControl) {
      FormSubmitControl.setEnabled($('#receivingSubmitBtn'), isReceivingValid());
    }
  }

  $(function () {
    if (!$('#receivingForm').length) {
      return;
    }

    FormSubmitControl.watch({
      form: '#receivingForm',
      button: '#receivingSubmitBtn',
      validate: function () {
        return isReceivingValid();
      },
    });

    $('#ingredientSelect, #quantityInput, #purchaseCostInput').on('change input', updateForm);
    $('input[name="receive_mode"]').on('change', updateForm);
    updateForm();
  });
})(jQuery);

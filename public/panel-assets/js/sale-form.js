(function ($) {
  'use strict';

  var data = window.saleFormData || { items: [], ingredients: [], customers: [] };
  var itemIndex = 0;
  var ingredientIndex = 0;
  var refreshSubmitState = null;

  function formatMoney(amount) {
    return Math.round(amount).toLocaleString() + ' TZS';
  }

  function itemById(id) {
    return data.items.find(function (item) {
      return String(item.id) === String(id);
    });
  }

  function ingredientById(id) {
    return data.ingredients.find(function (item) {
      return String(item.id) === String(id);
    });
  }

  function customerById(id) {
    return data.customers.find(function (customer) {
      return String(customer.id) === String(id);
    });
  }

  function buildItemOptions(selected) {
    var html = '<option value=""></option>';
    data.items.forEach(function (item) {
      var sel = String(item.id) === String(selected) ? ' selected' : '';
      html += '<option value="' + item.id + '" data-price="' + item.price + '"' + sel + '>' + item.name + '</option>';
    });
    return html;
  }

  function buildIngredientOptions(selected) {
    var html = '<option value=""></option>';
    data.ingredients.forEach(function (item) {
      var sel = String(item.id) === String(selected) ? ' selected' : '';
      html += '<option value="' + item.id + '" data-stock="' + item.stock + '" data-unit="' + (item.unit || '') + '"' + sel + '>' + item.name + '</option>';
    });
    return html;
  }

  function initSelect2($select, placeholder, dropdownParent) {
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    var options = {
      width: '100%',
      placeholder: placeholder,
      allowClear: true,
    };

    if (dropdownParent && dropdownParent.length) {
      options.dropdownParent = dropdownParent;
    }

    $select.select2(options);
  }

  function lineTotal(qty, price, discount) {
    var subtotal = qty * price;
    var total = subtotal - discount;
    return total > 0 ? total : 0;
  }

  function hasValidItems() {
    var valid = false;

    $('#saleItemsBody tr').each(function () {
      var itemId = $(this).find('.js-item-select').val();
      var qty = parseFloat($(this).find('.js-item-qty').val()) || 0;
      var price = parseFloat($(this).find('.js-item-price-input').val()) || 0;

      if (itemId && qty > 0 && price >= 0) {
        valid = true;
      }
    });

    return valid;
  }

  function hasValidIngredients() {
    var valid = false;

    $('#ingredientsBody tr').each(function () {
      var ingredientId = $(this).find('.js-ingredient-select').val();
      var qty = parseFloat($(this).find('.js-ingredient-qty').val()) || 0;

      if (ingredientId && qty > 0) {
        valid = true;
      }
    });

    return valid;
  }

  function validateSaleForm() {
    return hasValidItems() && hasValidIngredients();
  }

  function updateSubmitHint() {
    var $hint = $('#saleSubmitHint');
    if (!$hint.length) {
      return;
    }

    if (!hasValidItems()) {
      $hint.text('Add at least one item to save.');
    } else if (!hasValidIngredients()) {
      $hint.text('Add at least one ingredient used to save.');
    } else {
      $hint.text('');
    }
  }

  function updateItemRow($row) {
    var qty = parseFloat($row.find('.js-item-qty').val()) || 0;
    var price = parseFloat($row.find('.js-item-price-input').val()) || 0;
    var discount = parseFloat($row.find('.js-item-discount').val()) || 0;
    var total = lineTotal(qty, price, discount);

    $row.find('.js-item-price-display').text(formatMoney(price));
    $row.find('.js-item-total').text(formatMoney(total));
    updateGrandTotal();

    if (refreshSubmitState) {
      refreshSubmitState();
    }
  }

  function updateIngredientRow($row) {
    var ingredient = ingredientById($row.find('.js-ingredient-select').val());
    var stockText = ingredient ? (ingredient.stock + ' ' + (ingredient.unit || '')) : '—';
    $row.find('.js-ingredient-stock').text(stockText);

    if (refreshSubmitState) {
      refreshSubmitState();
    }
  }

  function updateGrandTotal() {
    var total = 0;
    $('#saleItemsBody tr').each(function () {
      var qty = parseFloat($(this).find('.js-item-qty').val()) || 0;
      var price = parseFloat($(this).find('.js-item-price-input').val()) || 0;
      var discount = parseFloat($(this).find('.js-item-discount').val()) || 0;
      total += lineTotal(qty, price, discount);
    });
    $('#saleGrandTotal').text(formatMoney(total));
  }

  function updateCustomerChip() {
    var name = $('#customerNameInput').val().trim();
    var phone = $('#customerPhoneInput').val().trim();
    var text = 'Walk-in customer';

    if (name && phone) {
      text = name + ' · ' + phone;
    } else if (name) {
      text = name;
    } else if (phone) {
      text = phone;
    }

    $('#customerChipText').text(text);
  }

  function syncModalFromHidden() {
    $('#modalCustomerSelect').val($('#customerIdInput').val() || '').trigger('change.select2');
    $('#modalCustomerName').val($('#customerNameInput').val());
    if (window.PhoneTz) {
      window.PhoneTz.setPhone($('#modalCustomerPhoneLocal'), $('#customerPhoneInput').val());
    }
    $('#modalSoldAt').val($('#soldAtInput').val());
  }

  function fillCustomerFieldsFromSelect() {
    var id = $('#modalCustomerSelect').val();
    if (!id) {
      return;
    }

    var customer = customerById(id);
    if (!customer) {
      return;
    }

    $('#modalCustomerName').val(customer.name);
    if (window.PhoneTz) {
      window.PhoneTz.setPhone($('#modalCustomerPhoneLocal'), customer.phone || '');
    }
  }

  function addItemRow(values) {
    values = values || {};
    var idx = itemIndex++;
    var $row = $(
      '<tr class="sale-form-row">' +
        '<td data-label="Item"><select class="form-control js-item-select" name="items[' + idx + '][item_id]" required>' + buildItemOptions(values.item_id) + '</select></td>' +
        '<td data-label="Qty"><input type="number" class="form-control js-item-qty" name="items[' + idx + '][quantity]" min="0.0001" step="any" value="' + (values.quantity || 1) + '" required></td>' +
        '<td data-label="Price" class="align-middle"><span class="js-item-price-display">0 TZS</span><input type="hidden" class="js-item-price-input" name="items[' + idx + '][unit_price]" value="' + (values.unit_price || 0) + '"></td>' +
        '<td data-label="Discount"><input type="number" class="form-control js-item-discount" name="items[' + idx + '][discount]" min="0" step="1" value="' + (values.discount || 0) + '"></td>' +
        '<td data-label="Total" class="js-item-total align-middle"><strong>0 TZS</strong></td>' +
        '<td class="sale-form-row-actions align-middle" data-label="Remove"><button type="button" class="btn btn-sm btn-danger js-remove-row"><i class="fa fa-trash"></i> Remove</button></td>' +
      '</tr>'
    );

    $('#saleItemsBody').append($row);
    var $select = $row.find('.js-item-select');
    initSelect2($select, 'Search item', $(document.body));

    $select.on('change', function () {
      var item = itemById($(this).val());
      if (item) {
        $row.find('.js-item-price-input').val(item.price);
      } else {
        $row.find('.js-item-price-input').val(0);
      }
      updateItemRow($row);
    });

    if (values.item_id) {
      var item = itemById(values.item_id);
      if (item) {
        $row.find('.js-item-price-input').val(values.unit_price ?? item.price);
      }
    }

    updateItemRow($row);
  }

  function addIngredientRow(values) {
    values = values || {};
    var idx = ingredientIndex++;
    var $row = $(
      '<tr class="sale-form-row">' +
        '<td data-label="Ingredient"><select class="form-control js-ingredient-select" name="ingredients[' + idx + '][ingredient_id]" required>' + buildIngredientOptions(values.ingredient_id) + '</select></td>' +
        '<td data-label="Qty used"><input type="number" class="form-control js-ingredient-qty" name="ingredients[' + idx + '][quantity_used]" min="0.0001" step="any" value="' + (values.quantity_used || '') + '" placeholder="0" required></td>' +
        '<td data-label="In stock" class="js-ingredient-stock align-middle text-muted">—</td>' +
        '<td class="sale-form-row-actions align-middle" data-label="Remove"><button type="button" class="btn btn-sm btn-danger js-remove-row"><i class="fa fa-trash"></i> Remove</button></td>' +
      '</tr>'
    );
    $('#ingredientsBody').append($row);
    var $select = $row.find('.js-ingredient-select');
    initSelect2($select, 'Search ingredient', $(document.body));
    $select.on('change', function () {
      updateIngredientRow($(this).closest('tr'));
    });
    $row.find('.js-ingredient-qty').on('input', function () {
      updateIngredientRow($row);
    });
    updateIngredientRow($row);
  }

  $(function () {
    if (!$('#saleForm').length) {
      return;
    }

    initSelect2($('#modalCustomerSelect'), 'Select customer', $('#customerModal'));
    syncModalFromHidden();
    updateCustomerChip();

    refreshSubmitState = FormSubmitControl.watch({
      form: '#saleForm',
      button: '#saleSubmitBtn',
      validate: function () {
        var ok = validateSaleForm();
        updateSubmitHint();
        return ok;
      },
      extraEvents: $('#saleItemsBody, #ingredientsBody'),
    });

    $('#customerModal').on('show.bs.modal', syncModalFromHidden);
    $('#modalCustomerSelect').on('change select2:select', fillCustomerFieldsFromSelect);

    $('#saveCustomerModal').on('click', function () {
      if (window.PhoneTz) {
        window.PhoneTz.syncPhoneFull($('#modalCustomerPhoneLocal'));
      }

      var customerId = $('#modalCustomerSelect').val();
      $('#customerIdInput').val(customerId || '');
      $('#customerNameInput').val($('#modalCustomerName').val().trim());
      $('#customerPhoneInput').val($('#modalCustomerPhoneFull').val().trim());
      $('#soldAtInput').val($('#modalSoldAt').val());
      updateCustomerChip();
      $('#customerModal').modal('hide');
    });

    $('#addSaleItem').on('click', function () {
      addItemRow();
      if (refreshSubmitState) {
        refreshSubmitState();
      }
    });

    $('#addIngredientRow').on('click', function () {
      addIngredientRow();
      if (refreshSubmitState) {
        refreshSubmitState();
      }
    });

    $('#saleItemsBody').on('input', '.js-item-qty, .js-item-discount', function () {
      updateItemRow($(this).closest('tr'));
    });

    $(document).on('click', '.js-remove-row', function () {
      var $row = $(this).closest('tr');
      var isItem = $row.closest('#saleItemsBody').length > 0;

      $row.find('select').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
          $(this).select2('destroy');
        }
      });

      $row.remove();

      if (isItem) {
        updateGrandTotal();
      }

      if (refreshSubmitState) {
        refreshSubmitState();
      }
    });

    var initial = window.saleFormInitial || {};

    if (initial.customer_id) {
      $('#customerIdInput').val(initial.customer_id);
    }

    if (initial.customer_name) {
      $('#customerNameInput').val(initial.customer_name);
    }

    if (initial.customer_phone) {
      $('#customerPhoneInput').val(initial.customer_phone);
    }

    if (initial.notes) {
      $('textarea[name="notes"]').val(initial.notes);
    }

    updateCustomerChip();

    if (initial.items && initial.items.length) {
      initial.items.forEach(function (row) {
        addItemRow(row);
      });
    } else {
      addItemRow();
    }

    if (initial.ingredients && initial.ingredients.length) {
      initial.ingredients.forEach(function (row) {
        addIngredientRow(row);
      });
    }

    updateGrandTotal();
    updateSubmitHint();
    if (refreshSubmitState) {
      refreshSubmitState();
    }
  });
})(jQuery);

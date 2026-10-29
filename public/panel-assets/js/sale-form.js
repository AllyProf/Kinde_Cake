(function ($) {
  'use strict';

  var data = window.saleFormData || { items: [], ingredients: [], customers: [], requireIngredients: true };
  var requireIngredients = data.requireIngredients !== false;
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
    var invalid = false;

    $('#saleItemsBody tr').each(function () {
      var $row = $(this);
      var itemId = $row.find('.js-item-select').val();
      if (!itemId) {
        return;
      }

      var item = itemById(itemId);
      var qty = parseFloat($row.find('.js-item-qty').val()) || 0;
      var price = parseFloat($row.find('.js-item-price-input').val()) || 0;
      var customName = ($row.find('.js-custom-item-name').val() || '').trim();

      if (qty <= 0) {
        invalid = true;
        return;
      }

      if (item && item.is_custom) {
        if (!customName || price <= 0) {
          invalid = true;
          return;
        }
      } else if (price < 0) {
        invalid = true;
        return;
      }

      valid = true;
    });

    return valid && !invalid;
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

  function cartHasOrderItem() {
    var hasOrder = false;

    $('#saleItemsBody tr').each(function () {
      var item = itemById($(this).find('.js-item-select').val());
      if (item && item.sale_type === 'order') {
        hasOrder = true;
        return false;
      }
    });

    return hasOrder;
  }

  function cartTotal() {
    var total = 0;
    $('#saleItemsBody tr').each(function () {
      var qty = parseFloat($(this).find('.js-item-qty').val()) || 0;
      var price = parseFloat($(this).find('.js-item-price-input').val()) || 0;
      var discount = parseFloat($(this).find('.js-item-discount').val()) || 0;
      total += lineTotal(qty, price, discount);
    });
    return total;
  }

  function amountPaid() {
    return parseFloat($('#saleAdvanceAmount').val()) || 0;
  }

  function customerRequired() {
    if (cartHasOrderItem()) {
      return true;
    }

    var total = Math.round(cartTotal());
    if (total <= 0) {
      return false;
    }

    return Math.round(amountPaid()) < total;
  }

  function hasValidCustomer() {
    var name = ($('#customerNameInput').val() || '').trim();
    var phone = ($('#customerPhoneInput').val() || '').trim();
    var localDigits = phone.replace(/^\+255/, '').replace(/\D/g, '');

    return name.length > 0 && localDigits.length === 9;
  }

  function syncSaleModeUi() {
    var orderMode = cartHasOrderItem();
    var needsCustomer = customerRequired();
    var $orderFields = $('.js-sale-order-fields');
    var $requiredMark = $('.js-customer-required-mark');
    var $heading = $('#salePaymentHeading');
    var $advanceLabel = $('#saleAdvanceLabel');
    var $advanceHint = $('#saleAdvanceHint');

    $orderFields.toggleClass('d-none', !orderMode);
    $orderFields.find('input[name], select[name], textarea[name]').prop('disabled', !orderMode);
    $requiredMark.toggleClass('d-none', !needsCustomer);

    if ($heading.length) {
      $heading.text(orderMode ? 'Pickup & payment' : 'Payment');
    }
    if ($advanceLabel.length) {
      $advanceLabel.text(orderMode ? 'Pay now / advance (TZS)' : 'Amount paid (TZS)');
    }
    if ($advanceHint.length) {
      $advanceHint.text(
        orderMode
          ? '0 = pay later. Enter full total to mark paid in full.'
          : 'Pay in full for walk-in (no customer). Pay less and customer details are required.'
      );
    }

    updateCustomerChip();
  }

  function validateSaleForm() {
    if (customerRequired() && !hasValidCustomer()) {
      return false;
    }

    if (!hasValidItems()) {
      return false;
    }

    return requireIngredients ? hasValidIngredients() : true;
  }

  function updateSubmitHint() {
    var $hint = $('#saleSubmitHint');
    if (!$hint.length) {
      return;
    }

    if (customerRequired() && !hasValidCustomer()) {
      $hint.text(
        cartHasOrderItem()
          ? 'Add customer name and phone before saving.'
          : 'Customer name and phone are required when amount paid is less than the total.'
      );
    } else if (!hasValidItems()) {
      var needsCustomDetails = false;
      $('#saleItemsBody tr').each(function () {
        var item = itemById($(this).find('.js-item-select').val());
        if (item && item.is_custom) {
          var name = ($(this).find('.js-custom-item-name').val() || '').trim();
          var price = parseFloat($(this).find('.js-item-price-input').val()) || 0;
          if (!name || price <= 0) {
            needsCustomDetails = true;
            return false;
          }
        }
      });
      $hint.text(
        needsCustomDetails
          ? 'Enter the custom cake name and price before saving.'
          : 'Add at least one item to save.'
      );
    } else if (requireIngredients && !hasValidIngredients()) {
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
    var item = itemById($row.find('.js-item-select').val());

    if (!(item && item.is_custom)) {
      $row.find('.js-item-price-display').text(formatMoney(price));
    }
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
    $('#saleGrandTotal').text(formatMoney(cartTotal()));
    syncSaleModeUi();
  }

  function updateCustomerChip() {
    var name = ($('#customerNameInput').val() || '').trim();
    var phone = ($('#customerPhoneInput').val() || '').trim();
    var $chip = $('#customerChip');
    var needsCustomer = customerRequired();
    var text = needsCustomer ? 'Customer required — tap Customer' : 'Optional customer — tap to add';
    var complete = hasValidCustomer();
    var missing = needsCustomer && !complete;

    if (name && phone) {
      text = name + ' · ' + phone;
    } else if (name) {
      text = name + (needsCustomer ? ' · phone required' : ' · phone optional');
    } else if (phone) {
      text = (needsCustomer ? 'Name required · ' : 'Name optional · ') + phone;
    }

    $('#customerChipText').text(text);
    $chip.toggleClass('is-missing', missing);
    $chip.toggleClass('is-complete', complete);

    if (refreshSubmitState) {
      refreshSubmitState();
    }
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
    if (customer.birthday && $('#saleCustomerBirthday').length) {
      $('#saleCustomerBirthday').val(customer.birthday);
    }
  }

  function syncCustomItemFields($row, values) {
    values = values || {};
    var item = itemById($row.find('.js-item-select').val());
    var isCustom = !!(item && item.is_custom);
    var $nameWrap = $row.find('.js-custom-name-wrap');
    var $nameInput = $row.find('.js-custom-item-name');
    var $priceDisplay = $row.find('.js-item-price-display');
    var $priceInput = $row.find('.js-item-price-input');

    $nameWrap.toggleClass('d-none', !isCustom);
    $nameInput.prop('disabled', !isCustom);
    if (!isCustom) {
      $nameInput.val('');
    } else if (values.item_name) {
      $nameInput.val(values.item_name);
    }

    if (isCustom) {
      $priceDisplay.addClass('d-none');
      $priceInput
        .attr({ type: 'text', inputmode: 'numeric', placeholder: 'Enter price' })
        .addClass('form-control')
        .removeClass('d-none')
        .prop('readonly', false);
      if (values.unit_price !== undefined && values.unit_price !== null && values.unit_price !== '') {
        $priceInput.val(values.unit_price);
      } else if (!$priceInput.val() || parseFloat($priceInput.val()) === 0) {
        $priceInput.val('');
      }
    } else {
      $priceDisplay.removeClass('d-none');
      $priceInput
        .attr({ type: 'hidden', placeholder: '' })
        .removeClass('form-control')
        .addClass('d-none')
        .prop('readonly', true);
      if (item) {
        $priceInput.val(values.unit_price !== undefined && values.unit_price !== null ? values.unit_price : item.price);
      } else {
        $priceInput.val(0);
      }
    }
  }

  function addItemRow(values) {
    values = values || {};
    var idx = itemIndex++;
    var $row = $(
      '<tr class="sale-form-row">' +
        '<td data-label="Item">' +
          '<select class="form-control js-item-select" name="items[' + idx + '][item_id]" required>' + buildItemOptions(values.item_id) + '</select>' +
          '<div class="js-custom-name-wrap mt-1 d-none">' +
            '<input type="text" class="form-control form-control-sm js-custom-item-name" name="items[' + idx + '][item_name]" maxlength="255" placeholder="Custom cake name" disabled>' +
          '</div>' +
        '</td>' +
        '<td data-label="Qty" class="align-top"><input type="number" class="form-control js-item-qty" name="items[' + idx + '][quantity]" min="0.0001" step="any" value="' + (values.quantity || 1) + '" required></td>' +
        '<td data-label="Price" class="align-top sale-form-price-cell">' +
          '<span class="js-item-price-display">0 TZS</span>' +
          '<input type="hidden" class="form-control js-item-price-input d-none" name="items[' + idx + '][unit_price]" value="' + (values.unit_price || 0) + '" inputmode="numeric" autocomplete="off">' +
        '</td>' +
        '<td data-label="Discount" class="align-top"><input type="number" class="form-control js-item-discount" name="items[' + idx + '][discount]" min="0" step="1" value="' + (values.discount || 0) + '"></td>' +
        '<td data-label="Total" class="js-item-total align-top"><strong>0 TZS</strong></td>' +
        '<td class="sale-form-row-actions align-top" data-label="Remove"><button type="button" class="btn btn-sm btn-danger js-remove-row"><i class="fa fa-trash"></i> Remove</button></td>' +
      '</tr>'
    );

    $('#saleItemsBody').append($row);
    var $select = $row.find('.js-item-select');
    initSelect2($select, 'Search item', $(document.body));

    $select.on('change', function () {
      syncCustomItemFields($row);
      updateItemRow($row);
      syncSaleModeUi();
    });

    syncCustomItemFields($row, values);
    updateItemRow($row);
  }

  function addIngredientRow(values) {
    if (!$('#ingredientsBody').length) {
      return;
    }

    values = values || {};
    var idx = ingredientIndex++;
    var requiredAttr = requireIngredients ? ' required' : '';
    var $row = $(
      '<tr class="sale-form-row">' +
        '<td data-label="Ingredient"><select class="form-control js-ingredient-select" name="ingredients[' + idx + '][ingredient_id]"' + requiredAttr + '>' + buildIngredientOptions(values.ingredient_id) + '</select></td>' +
        '<td data-label="Qty used"><input type="number" class="form-control js-ingredient-qty" name="ingredients[' + idx + '][quantity_used]" min="0.0001" step="any" value="' + (values.quantity_used || '') + '" placeholder="0"' + requiredAttr + '></td>' +
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
      extraEvents: $('#saleItemsBody, #ingredientsBody, #saleAdvanceAmount'),
    });

    $('#saleAdvanceAmount').on('input change', function () {
      syncSaleModeUi();
    });

    $('#customerModal').on('show.bs.modal', syncModalFromHidden);
    $('#modalCustomerSelect').on('change select2:select', fillCustomerFieldsFromSelect);

    $('#saveCustomerModal').on('click', function () {
      if (window.PhoneTz) {
        window.PhoneTz.syncPhoneFull($('#modalCustomerPhoneLocal'));
      }

      var name = ($('#modalCustomerName').val() || '').trim();
      var phoneLocal = ($('#modalCustomerPhoneLocal').val() || '').replace(/\D/g, '');
      var $nameInput = $('#modalCustomerName');
      var $phoneLocal = $('#modalCustomerPhoneLocal');
      var $error = $('#customerModalError');

      $nameInput.removeClass('is-invalid');
      $phoneLocal.removeClass('is-invalid');
      if ($error.length) {
        $error.addClass('d-none').text('');
      }

      if (!name || phoneLocal.length !== 9) {
        if (!name) {
          $nameInput.addClass('is-invalid');
        }
        if (phoneLocal.length !== 9) {
          $phoneLocal.addClass('is-invalid');
        }
        if ($error.length) {
          $error.removeClass('d-none').text('Customer name and a valid 9-digit phone are required.');
        }
        return;
      }

      var customerId = $('#modalCustomerSelect').val();
      $('#customerIdInput').val(customerId || '');
      $('#customerNameInput').val(name);
      $('#customerPhoneInput').val($('#modalCustomerPhoneFull').val().trim());
      $('#soldAtInput').val($('#modalSoldAt').val());

      if (customerId && $('#saleCustomerBirthday').length) {
        var customer = customerById(customerId);
        if (customer && customer.birthday && !$('#saleCustomerBirthday').val()) {
          $('#saleCustomerBirthday').val(customer.birthday);
        }
      }

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

    $('#saleItemsBody').on('input', '.js-item-qty, .js-item-discount, .js-item-price-input, .js-custom-item-name', function () {
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
        syncSaleModeUi();
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
    syncSaleModeUi();
    updateSubmitHint();
    if (refreshSubmitState) {
      refreshSubmitState();
    }
  });
})(jQuery);

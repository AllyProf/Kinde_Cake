(function ($) {
  'use strict';

  var providers = window.salesPaymentProviders || [];
  var searchTimer = null;
  var currentBalanceDue = 0;

  function formatMoney(amount) {
    return Math.round(amount).toLocaleString() + ' TZS';
  }

  function providersForMethod(method) {
    if (method === 'mobile') {
      return providers.filter(function (provider) {
        return provider.type === 'mobile';
      });
    }

    if (method === 'bank') {
      return providers.filter(function (provider) {
        return provider.type === 'bank';
      });
    }

    return [];
  }

  function fillProviderOptions(method) {
    var $select = $('#payProviderSelect');
    var list = providersForMethod(method);
    var html = '<option value="">Select provider</option>';

    list.forEach(function (provider) {
      html += '<option value="' + provider.id + '">' + provider.name + '</option>';
    });

    $select.html(html);
  }

  function initPayCustomerSelect2() {
    var $select = $('#payCreditCustomerSelect');

    if (!$select.length || typeof $.fn.select2 !== 'function') {
      return;
    }

    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      width: '100%',
      placeholder: 'Search customer',
      allowClear: true,
      dropdownParent: $('#paySaleModal'),
    });
  }

  function setPayCustomerSelectValue(value) {
    var $select = $('#payCreditCustomerSelect');

    if (!$select.length) {
      return;
    }

    if ($select.hasClass('select2-hidden-accessible')) {
      $select.val(value || null).trigger('change.select2');
      return;
    }

    $select.val(value || '');
  }

  function clearBalanceInfoFields() {
    setPayCustomerSelectValue('');
    $('#payCreditCustomerId').val('');
    $('#payCreditCustomerName').val('').prop('required', false);
    $('#payCreditCustomerPhone').val('');
    $('#payCreditRepaymentDate').val('').prop('required', false);
  }

  function setBalanceInfoFromSale($btn) {
    clearBalanceInfoFields();

    var customerId = $btn.data('customer-id') || '';
    var customerName = $btn.data('customer-name') || '';
    var customerPhone = $btn.data('customer-phone') || '';
    var repaymentDate = $btn.data('repayment-date') || '';

    if (customerId) {
      setPayCustomerSelectValue(String(customerId));
      $('#payCreditCustomerId').val(customerId);
    }

    $('#payCreditCustomerName').val(customerName);
    $('#payCreditCustomerPhone').val(customerPhone);
    $('#payCreditRepaymentDate').val(repaymentDate);
  }

  function fillCustomerFieldsFromSelect() {
    var $select = $('#payCreditCustomerSelect');
    var $option = $select.find('option:selected');
    var id = $select.val();

    if (!id) {
      $('#payCreditCustomerId').val('');
      return;
    }

    $('#payCreditCustomerId').val(id);
    $('#payCreditCustomerName').val($option.data('name') || '');
    $('#payCreditCustomerPhone').val($option.data('phone') || '');
  }

  function currentPayAmount() {
    return parseFloat($('#payAmountInput').val()) || 0;
  }

  function requiresBalanceInfo(method, amount) {
    if (method === 'credit') {
      return true;
    }

    if (currentBalanceDue <= 0) {
      return false;
    }

    return amount > 0 && amount < currentBalanceDue;
  }

  function updateBalanceInfoFields() {
    var method = $('#payMethodSelect').val();
    var amount = currentPayAmount();
    var needsInfo = requiresBalanceInfo(method, amount);
    var $group = $('#payBalanceInfoGroup');

    if (needsInfo) {
      $group.removeClass('d-none');
      $('#payCreditCustomerName').prop('required', true);
      $('#payCreditRepaymentDate').prop('required', true);
      initPayCustomerSelect2();
    } else {
      $group.addClass('d-none');
      $('#payCreditCustomerName').prop('required', false);
      $('#payCreditRepaymentDate').prop('required', false);
    }
  }

  function setPayAmount(value) {
    var amount = Math.max(1, Math.round(parseFloat(value) || 0));
    if (currentBalanceDue > 0) {
      amount = Math.min(amount, Math.round(currentBalanceDue));
    }
    $('#payAmountInput').attr('max', currentBalanceDue).val(amount);
    updatePaySubmitLabel(amount);
    updateBalanceInfoFields();
  }

  function updatePaySubmitLabel(amount) {
    var $btn = $('#paySubmitBtn');
    if (!$btn.length) {
      return;
    }

    var method = $('#payMethodSelect').val();
    var balance = currentBalanceDue;

    if (method === 'credit') {
      $btn.html('<i class="fa fa-check"></i> Record credit (debt)');
      return;
    }

    if (amount >= balance && balance > 0) {
      $btn.html('<i class="fa fa-check"></i> Pay full balance');
    } else {
      $btn.html('<i class="fa fa-check"></i> Record partial payment');
    }
  }

  function togglePaymentFields(method) {
    var isMobileOrBank = method === 'mobile' || method === 'bank';
    var $providerGroup = $('#payProviderGroup');
    var $providerSelect = $('#payProviderSelect');
    var $referenceGroup = $('#payReferenceGroup');

    if (isMobileOrBank) {
      $('#payProviderLabel').text(method === 'mobile' ? 'Mobile provider' : 'Bank provider');
      fillProviderOptions(method);
      $providerGroup.removeClass('d-none');
      $providerSelect.prop('required', true);
      $referenceGroup.removeClass('d-none');
    } else {
      $providerGroup.addClass('d-none');
      $providerSelect.prop('required', false).val('');
      $referenceGroup.addClass('d-none');
      $('#payReferenceInput').val('');
    }

    updateBalanceInfoFields();
  }

  function submitFilters($form) {
    $form.trigger('submit');
  }

  function filterRowsInstantly(query, rowSelector, tableBodySelector, noMatchSelector) {
    var term = $.trim(query).toLowerCase();
    var visible = 0;

    $(tableBodySelector + ' ' + rowSelector).each(function () {
      var haystack = $(this).data('search') || '';
      var matches = !term || haystack.indexOf(term) !== -1;
      $(this).toggle(matches);
      if (matches) {
        visible++;
      }
    });

    var hasServerRows = $(tableBodySelector + ' ' + rowSelector).length > 0;
    $(noMatchSelector).toggleClass('d-none', !hasServerRows || visible > 0 || !term);
  }

  function bindFilterForm(formSelector, searchSelector, filterSelector, rowSelector, tableBodySelector, noMatchSelector) {
    var $form = $(formSelector);
    var $search = $(searchSelector);

    if (!$form.length) {
      return;
    }

    $search.on('input', function () {
      var value = $(this).val();
      filterRowsInstantly(value, rowSelector, tableBodySelector, noMatchSelector);

      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        submitFilters($form);
      }, 400);
    });

    $(filterSelector).on('change', function () {
      submitFilters($form);
    });

    $form.on('submit', function () {
      clearTimeout(searchTimer);
    });
  }

  $(function () {
    bindFilterForm(
      '#salesFilterForm',
      '#salesSearchInput',
      '.js-sales-filter',
      '.js-sale-row',
      '#salesTableBody',
      '#salesNoMatchRow'
    );

    bindFilterForm(
      '#debtsFilterForm',
      '#debtsSearchInput',
      '.js-debts-filter',
      '.js-debt-row',
      '#debtsTableBody',
      '#debtsNoMatchRow'
    );

    if ($('#payCreditCustomerSelect').length) {
      initPayCustomerSelect2();
    }

    $('#paySaleModal').on('shown.bs.modal', function () {
      initPayCustomerSelect2();
    });

    $('.js-pay-sale').on('click', function () {
      var $btn = $(this);
      var balanceDue = parseFloat($btn.data('balance-due')) || parseFloat($btn.data('sale-total-raw')) || 0;
      var amountPaid = parseFloat($btn.data('amount-paid')) || 0;

      currentBalanceDue = balanceDue;

      $('#paySaleForm').attr('action', $btn.data('pay-url'));
      $('#paySaleNumber').text($btn.data('sale-number'));
      $('#paySaleTotal').text($btn.data('sale-total'));
      $('#paySalePaid').text(formatMoney(amountPaid));
      $('#paySaleBalance').text(formatMoney(balanceDue));
      $('#payMethodSelect').val('');
      $('#payReferenceInput').val('');
      setBalanceInfoFromSale($btn);
      setPayAmount(balanceDue);
      togglePaymentFields('');
      $('#paySaleModal').modal('show');
    });

    $('#payFullBalanceBtn').on('click', function () {
      setPayAmount(currentBalanceDue);
    });

    $('#payAmountInput').on('input', function () {
      var amount = parseFloat($(this).val()) || 0;
      updatePaySubmitLabel(amount);
      updateBalanceInfoFields();
    });

    $('#payMethodSelect').on('change', function () {
      togglePaymentFields($(this).val());
    });

    $('#payCreditCustomerSelect').on('change select2:select select2:clear', fillCustomerFieldsFromSelect);

    $('#payCreditCustomerName').on('input', function () {
      if ($(this).val().trim()) {
        setPayCustomerSelectValue('');
        $('#payCreditCustomerId').val('');
      }
    });
  });
})(jQuery);

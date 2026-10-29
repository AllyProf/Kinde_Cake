@extends('layouts.app')

@section('title', 'New Sale')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-plus"></i> New Sale</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
      <li class="breadcrumb-item">New</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <div></div>
          <p>
            <button type="button" class="btn btn-primary icon-btn" data-toggle="modal" data-target="#customerModal">
              <i class="fa fa-user"></i> Customer <span class="js-customer-required-mark text-warning d-none">*</span>
            </button>
          </p>
        </div>
        <div class="tile-body">
          @if($items->isEmpty())
            <div class="alert alert-warning mb-0">
              No items to sell yet.
              @if(auth()->user()->isOwner())
                <a href="{{ route('items.create') }}">Register items</a> first.
              @else
                Ask the owner to register items first.
              @endif
            </div>
          @else
            @if($errors->has('items') || $errors->has('customer_name') || $errors->has('customer_phone'))
              <div class="alert alert-danger">
                {{ $errors->first('customer_name') ?: ($errors->first('customer_phone') ?: $errors->first('items')) }}
              </div>
            @endif

            <div class="sale-customer-chip mb-3 {{ filled(old('customer_name', $saleFormInitial['customer_name'] ?? '')) && filled(old('customer_phone', $saleFormInitial['customer_phone'] ?? '')) ? 'is-complete' : '' }}" id="customerChip" role="button" data-toggle="modal" data-target="#customerModal" style="cursor:pointer;">
              <i class="fa fa-user"></i> <span id="customerChipText">Optional customer — tap to add</span>
            </div>

            <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
              @csrf
              @if(! empty($cakePointSale))
                <input type="hidden" name="cake_point_sale_id" value="{{ $cakePointSale->id }}">
                <div class="alert alert-info">
                  <i class="fa fa-birthday-cake"></i>
                  Creating sale from order <strong>{{ $cakePointSale->sale_number }}</strong>.
                  Customer and items are pre-filled — save when ready.
                </div>
              @endif
              <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id', $saleFormInitial['customer_id'] ?? '') }}">
              <input type="hidden" name="customer_name" id="customerNameInput" value="{{ old('customer_name', $saleFormInitial['customer_name'] ?? '') }}">
              <input type="hidden" name="customer_phone" id="customerPhoneInput" value="{{ old('customer_phone', $saleFormInitial['customer_phone'] ?? '') }}">
              <input type="hidden" name="sold_at" id="soldAtInput" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}">

              <div class="d-flex justify-content-between align-items-center mb-2 sale-form-section-head">
                <h5 class="mb-0">Items</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addSaleItem">
                  <i class="fa fa-plus"></i> Add
                </button>
              </div>
              <div class="table-responsive mb-3 sale-form-list">
                <table class="table table-bordered mb-0 sale-form-table detail-card-table" id="saleItemsTable">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th class="sale-form-col-qty">Qty</th>
                      <th class="sale-form-col-price">Price</th>
                      <th class="sale-form-col-discount">Discount</th>
                      <th class="sale-form-col-total">Total</th>
                      <th class="sale-form-col-actions"></th>
                    </tr>
                  </thead>
                  <tbody id="saleItemsBody"></tbody>
                </table>
              </div>

              <div class="sale-total-bar mb-3">
                Total: <span id="saleGrandTotal">0 TZS</span>
              </div>

              @php
                $existingAdvance = (float) ($saleFormInitial['existing_advance'] ?? 0);
              @endphp

              <h5 class="mb-3" id="salePaymentHeading">Payment</h5>
              <div class="row">
                <div class="col-12 col-lg-4 col-md-6 js-sale-order-fields">
                  <div class="form-group">
                    <label class="control-label" for="saleReadyAt">Ready / pickup date</label>
                    <input type="datetime-local" class="form-control @error('ready_at') is-invalid @enderror"
                      id="saleReadyAt" name="ready_at"
                      value="{{ old('ready_at', $saleFormInitial['ready_at'] ?? '') }}">
                    @error('ready_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">When the cake / order should be ready.</small>
                  </div>
                </div>
                <div class="col-12 col-lg-4 col-md-6 js-sale-order-fields">
                  <div class="form-group">
                    <label class="control-label" for="saleCustomerBirthday">Customer birthday</label>
                    <input type="date" class="form-control @error('customer_birthday') is-invalid @enderror"
                      id="saleCustomerBirthday" name="customer_birthday"
                      value="{{ old('customer_birthday', $saleFormInitial['customer_birthday'] ?? '') }}">
                    @error('customer_birthday')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">For birthday wish &amp; reminder SMS.</small>
                  </div>
                </div>
                <div class="col-12 col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="control-label" for="saleAdvanceAmount" id="saleAdvanceLabel">Amount paid (TZS)</label>
                    <input type="number" class="form-control @error('advance_amount') is-invalid @enderror"
                      id="saleAdvanceAmount" name="advance_amount" min="0" step="1"
                      value="{{ old('advance_amount', 0) }}" placeholder="0">
                    @error('advance_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted" id="saleAdvanceHint">Enter the amount received now. Leave 0 to pay later.</small>
                  </div>
                </div>
              </div>

              @if($existingAdvance > 0)
                <div class="alert alert-info py-2">
                  <i class="fa fa-info-circle"></i>
                  This order already has an advance of <strong>{{ number_format($existingAdvance, 0) }} TZS</strong>
                  that will move to this sale. Enter an extra amount above only if the customer pays more now.
                </div>
              @endif

              <div class="row js-sale-payment-fields">
                <div class="col-12 col-lg-4 col-md-6">
                  <div class="form-group">
                    <label class="control-label" for="salePaymentMethod">Payment method</label>
                    <select class="form-control @error('payment_method') is-invalid @enderror"
                      id="salePaymentMethod" name="payment_method">
                      <option value="">Select...</option>
                      @foreach(\App\Models\Sale::paymentMethodOptions() as $value => $label)
                        @if($value !== \App\Models\Sale::PAYMENT_CREDIT)
                          <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                        @endif
                      @endforeach
                    </select>
                    @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-12 col-lg-4 col-md-6 js-sale-provider-field d-none">
                  <div class="form-group">
                    <label class="control-label" for="salePaymentProvider">Provider</label>
                    <select class="form-control @error('payment_provider_id') is-invalid @enderror"
                      id="salePaymentProvider" name="payment_provider_id">
                      <option value="">Select...</option>
                      @foreach($paymentProviderOptions as $provider)
                        <option value="{{ $provider['id'] }}" data-type="{{ $provider['type'] }}"
                          @selected(old('payment_provider_id') == $provider['id'])>
                          {{ $provider['name'] }}
                        </option>
                      @endforeach
                    </select>
                    @error('payment_provider_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-12 col-lg-4 col-md-6 js-sale-reference-field d-none">
                  <div class="form-group">
                    <label class="control-label" for="salePaymentReference">Reference</label>
                    <input type="text" class="form-control @error('payment_reference') is-invalid @enderror"
                      id="salePaymentReference" name="payment_reference"
                      value="{{ old('payment_reference') }}" maxlength="120">
                    @error('payment_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-12 col-lg-4 col-md-6 js-sale-balance-due-field d-none">
                  <div class="form-group">
                    <label class="control-label" for="saleCreditRepaymentDate">Balance due date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('credit_repayment_date') is-invalid @enderror"
                      id="saleCreditRepaymentDate" name="credit_repayment_date"
                      value="{{ old('credit_repayment_date') }}">
                    @error('credit_repayment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Defaults to ready / pickup date when left blank.</small>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="control-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
                  placeholder="Size, flavor, pickup notes...">{{ old('notes', $saleFormInitial['notes'] ?? '') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group js-sale-order-fields" id="saleSmsSection">
                @if($smsReady ?? false)
                  <input type="hidden" name="send_customer_sms" value="0">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input type="checkbox" class="form-check-input" name="send_customer_sms" value="1"
                        @checked(old('send_customer_sms', '1') == '1')>
                      Send confirmation SMS to customer
                    </label>
                  </div>
                  <small class="text-muted">Customer will receive an order confirmation on their phone after you save.</small>
                @else
                  <div class="form-check">
                    <label class="form-check-label">
                      <input type="checkbox" class="form-check-input" disabled>
                      Send confirmation SMS to customer
                    </label>
                  </div>
                  <small class="text-muted">Configure SMS in Settings to enable customer confirmation messages.</small>
                @endif
              </div>

              <div class="tile-footer px-0 pb-0 sale-form-footer">
                <button class="btn btn-primary" type="submit" id="saleSubmitBtn" disabled>
                  <i class="fa fa-check-circle"></i> Save
                </button>
                <a class="btn btn-secondary" href="{{ route('sales.index') }}">Cancel</a>
              </div>
              <small class="text-muted d-block mt-2" id="saleSubmitHint">
                Add at least one item to save. Customer is required for orders, or when payment is below the total.
              </small>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Customer</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="customerModalError" class="alert alert-danger py-2 d-none"></div>
          <div class="form-group">
            <label class="control-label">Select customer</label>
            <select class="form-control" id="modalCustomerSelect">
              <option value="">New customer</option>
              @foreach($customers as $customer)
                <option value="{{ $customer->id }}"
                  data-name="{{ $customer->name }}"
                  data-phone="{{ $customer->phone }}"
                  data-birthday="{{ $customer->birthday?->format('Y-m-d') }}">
                  {{ $customer->displayLabel() }}
                </option>
              @endforeach
            </select>
            @if(auth()->user()->hasPermission('customers.manage'))
              <small class="text-muted"><a href="{{ route('customers.create') }}" target="_blank">Register new customer</a></small>
            @endif
          </div>
          <div class="form-group">
            <label class="control-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="modalCustomerName" maxlength="255" placeholder="Customer name" required>
          </div>
          <div class="form-group">
            <label class="control-label">Phone <span class="text-danger">*</span></label>
            <div class="input-group tz-phone-group">
              <div class="input-group-prepend">
                <span class="input-group-text tz-phone-prefix">
                  <img src="https://flagcdn.com/w20/tz.png" srcset="https://flagcdn.com/w40/tz.png 2x" width="20" height="15" alt="Tanzania">
                  <span>+255</span>
                </span>
              </div>
              <input type="tel" class="form-control js-tz-phone-local" id="modalCustomerPhoneLocal"
                data-phone-full="#modalCustomerPhoneFull"
                placeholder="7XX XXX XXX"
                inputmode="numeric"
                maxlength="9"
                autocomplete="tel-national"
                required>
              <input type="hidden" id="modalCustomerPhoneFull" value="">
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="control-label">Date</label>
            <input type="datetime-local" class="form-control" id="modalSoldAt"
              value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="saveCustomerModal" data-no-loader>Save</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .sale-customer-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 0.45rem 0.75rem;
      background: #f4f5f7;
      border-radius: 4px;
      font-size: 0.9rem;
      border: 1px solid transparent;
    }

    .sale-customer-chip.is-missing {
      background: #fff3cd;
      border-color: #ffeeba;
      color: #856404;
    }

    .sale-customer-chip.is-complete {
      background: #e8f5e9;
      border-color: #c8e6c9;
      color: #2e7d32;
    }

    .sale-total-bar {
      text-align: right;
      font-size: 1.1rem;
      font-weight: 700;
      padding: 0.75rem 1rem;
      background: #f4f5f7;
      border-radius: 4px;
    }

    .sale-total-bar span {
      color: var(--brand, #7c461f);
    }

    .sale-form-table .select2-container {
      width: 100% !important;
    }

    #saleItemsTable td.align-top {
      vertical-align: top;
    }

    #saleItemsTable .sale-form-price-cell .js-item-price-display {
      display: inline-block;
      min-height: calc(1.5em + 0.75rem + 2px);
      line-height: calc(1.5em + 0.75rem + 2px);
    }

    #saleItemsTable .sale-form-price-cell .js-item-price-input.form-control {
      width: 100%;
      min-width: 0;
    }

    #saleItemsTable .js-item-qty,
    #saleItemsTable .js-item-discount,
    #saleItemsTable .js-item-price-input {
      -moz-appearance: textfield;
      appearance: textfield;
    }

    #saleItemsTable .js-item-qty::-webkit-inner-spin-button,
    #saleItemsTable .js-item-qty::-webkit-outer-spin-button,
    #saleItemsTable .js-item-discount::-webkit-inner-spin-button,
    #saleItemsTable .js-item-discount::-webkit-outer-spin-button,
    #saleItemsTable .js-item-price-input::-webkit-inner-spin-button,
    #saleItemsTable .js-item-price-input::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    @media (min-width: 768px) {
      #saleItemsTable .sale-form-col-qty { width: 100px; }
      #saleItemsTable .sale-form-col-price { width: 130px; }
      #saleItemsTable .sale-form-col-discount { width: 130px; }
      #saleItemsTable .sale-form-col-total { width: 130px; }
      #saleItemsTable .sale-form-col-actions { width: 50px; }
    }

    .tz-phone-prefix {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      background: #f8f9fa;
      border-color: #ced4da;
    }

    .tz-phone-group .form-control {
      border-left: 0;
    }
  </style>
@endpush

@push('scripts')
  <script src="{{ asset('panel-assets/js/plugins/select2.min.js') }}"></script>
  <script src="{{ asset('panel-assets/js/phone-tz.js') }}"></script>
  <script>
    window.saleFormData = {
      items: @json($itemOptions),
      ingredients: [],
      customers: @json($customerOptions),
      requireIngredients: false,
    };
    window.saleFormInitial = @json($saleFormInitial ?? ['items' => [], 'ingredients' => []]);
    window.salesPaymentProviders = @json($paymentProviderOptions);
  </script>
  <script src="{{ asset('panel-assets/js/sale-form.js') }}"></script>
  <script>
    (function () {
      var advanceAmount = document.getElementById('saleAdvanceAmount');
      var paymentMethod = document.getElementById('salePaymentMethod');
      var providerField = document.querySelector('.js-sale-provider-field');
      var referenceField = document.querySelector('.js-sale-reference-field');
      var balanceDueField = document.querySelector('.js-sale-balance-due-field');
      var providerSelect = document.getElementById('salePaymentProvider');
      var readyAt = document.getElementById('saleReadyAt');
      var balanceDue = document.getElementById('saleCreditRepaymentDate');
      var birthdayInput = document.getElementById('saleCustomerBirthday');
      var customerSelect = document.getElementById('modalCustomerSelect');

      function syncPaymentFields() {
        var amount = parseFloat(advanceAmount && advanceAmount.value) || 0;
        var method = paymentMethod ? paymentMethod.value : '';
        var needsProvider = method === 'mobile' || method === 'bank';

        if (paymentMethod) {
          paymentMethod.required = amount > 0;
        }
        if (providerField) {
          providerField.classList.toggle('d-none', !needsProvider);
        }
        if (referenceField) {
          referenceField.classList.toggle('d-none', !needsProvider);
        }

        if (providerSelect && window.salesPaymentProviders) {
          var options = providerSelect.querySelectorAll('option[data-type]');
          options.forEach(function (option) {
            var show = !needsProvider || option.getAttribute('data-type') === method;
            option.hidden = !show;
            if (!show && option.selected) {
              providerSelect.value = '';
            }
          });
        }

        // Balance due date visibility is handled by sale-form.js
        // (only when amount paid leaves a remaining balance).
        if (readyAt && balanceDue && balanceDueField && !balanceDueField.classList.contains('d-none') && !balanceDue.value && readyAt.value) {
          balanceDue.value = readyAt.value.slice(0, 10);
        }
      }

      if (advanceAmount) {
        advanceAmount.addEventListener('input', syncPaymentFields);
      }
      if (paymentMethod) {
        paymentMethod.addEventListener('change', syncPaymentFields);
      }
      if (readyAt) {
        readyAt.addEventListener('change', syncPaymentFields);
      }

      if (customerSelect && birthdayInput) {
        customerSelect.addEventListener('change', function () {
          var option = customerSelect.options[customerSelect.selectedIndex];
          if (option && option.dataset.birthday) {
            birthdayInput.value = option.dataset.birthday;
          }
        });
      }

      syncPaymentFields();
    })();
  </script>
@endpush

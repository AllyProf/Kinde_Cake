@extends('layouts.app')

@section('title', 'Edit Sale')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-edit"></i> Edit {{ $sale->sale_number }}</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
      <li class="breadcrumb-item">{{ $sale->sale_number }}</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <div></div>
          <p>
            <button type="button" class="btn btn-secondary icon-btn" data-toggle="modal" data-target="#customerModal">
              <i class="fa fa-user"></i> Customer
            </button>
          </p>
        </div>
        <div class="tile-body">
          @if($errors->has('ingredients') || $errors->has('items') || $errors->has('sale'))
            <div class="alert alert-danger">{{ $errors->first('ingredients') ?: ($errors->first('items') ?: $errors->first('sale')) }}</div>
          @endif

          <div class="sale-customer-chip mb-3" id="customerChip">
            <i class="fa fa-user"></i> <span id="customerChipText">Walk-in customer</span>
          </div>

          <form action="{{ route('sales.update', $sale) }}" method="POST" id="saleForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="customer_id" id="customerIdInput" value="{{ old('customer_id', $sale->customer_id) }}">
            <input type="hidden" name="customer_name" id="customerNameInput" value="{{ old('customer_name', $sale->customer_name) }}">
            <input type="hidden" name="customer_phone" id="customerPhoneInput" value="{{ old('customer_phone', $sale->customer_phone) }}">
            <input type="hidden" name="sold_at" id="soldAtInput" value="{{ old('sold_at', $sale->sold_at->format('Y-m-d\TH:i')) }}">

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

            <div class="d-flex justify-content-between align-items-center mb-2 sale-form-section-head">
              <h5 class="mb-0">Ingredients used</h5>
              <button type="button" class="btn btn-sm btn-outline-primary" id="addIngredientRow">
                <i class="fa fa-plus"></i> Add
              </button>
            </div>
            <div class="table-responsive mb-3 sale-form-list">
              <table class="table table-bordered mb-0 sale-form-table detail-card-table" id="ingredientsTable">
                <thead>
                  <tr>
                    <th>Ingredient</th>
                    <th class="sale-form-col-qty">Qty used</th>
                    <th class="sale-form-col-stock">In stock</th>
                    <th class="sale-form-col-actions"></th>
                  </tr>
                </thead>
                <tbody id="ingredientsBody"></tbody>
              </table>
            </div>

            <div class="sale-total-bar mb-3">
              Total: <span id="saleGrandTotal">0 TZS</span>
            </div>

            <div class="form-group">
              <label class="control-label">Notes</label>
              <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes', $sale->notes) }}</textarea>
              @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="tile-footer px-0 pb-0 sale-form-footer">
              <button class="btn btn-primary" type="submit" id="saleSubmitBtn" disabled>
                <i class="fa fa-check-circle"></i> Save Changes
              </button>
              <a class="btn btn-secondary" href="{{ route('sales.index') }}">Back</a>
            </div>
            <small class="text-muted d-block mt-2" id="saleSubmitHint">Add at least one item and one ingredient to save.</small>
          </form>
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
          <div class="form-group">
            <label class="control-label">Select customer</label>
            <select class="form-control" id="modalCustomerSelect">
              <option value="">Walk-in / new</option>
              @foreach($customers as $customer)
                <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}">
                  {{ $customer->displayLabel() }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="control-label">Name</label>
            <input type="text" class="form-control" id="modalCustomerName" maxlength="255" placeholder="Optional">
          </div>
          <div class="form-group">
            <label class="control-label">Phone</label>
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
                autocomplete="tel-national">
              <input type="hidden" id="modalCustomerPhoneFull" value="">
            </div>
          </div>
          <div class="form-group mb-0">
            <label class="control-label">Date</label>
            <input type="datetime-local" class="form-control" id="modalSoldAt"
              value="{{ old('sold_at', $sale->sold_at->format('Y-m-d\TH:i')) }}">
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

    @media (min-width: 768px) {
      #saleItemsTable .sale-form-col-qty { width: 100px; }
      #saleItemsTable .sale-form-col-price { width: 130px; }
      #saleItemsTable .sale-form-col-discount { width: 130px; }
      #saleItemsTable .sale-form-col-total { width: 130px; }
      #saleItemsTable .sale-form-col-actions { width: 50px; }
      #ingredientsTable .sale-form-col-qty { width: 160px; }
      #ingredientsTable .sale-form-col-stock { width: 120px; }
      #ingredientsTable .sale-form-col-actions { width: 50px; }
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
      ingredients: @json($ingredientOptions),
      customers: @json($customerOptions),
    };
    window.saleFormInitial = {
      items: @json($initialItems),
      ingredients: @json($initialIngredients),
    };
  </script>
  <script src="{{ asset('panel-assets/js/sale-form.js') }}"></script>
@endpush

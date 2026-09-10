@extends('layouts.app')

@section('title', 'Receive Stock')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-plus"></i> Receive Stock</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('receivings.index') }}">Receiving</a></li>
      <li class="breadcrumb-item">Receive</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="tile">
        <h3 class="tile-title">Details</h3>
        <div class="tile-body">
          @if($ingredients->isEmpty())
            <div class="alert alert-warning mb-0">
              No ingredients yet. <a href="{{ route('ingredients.create') }}">Add one</a> first.
            </div>
          @else
            <form action="{{ route('receivings.store') }}" method="POST" id="receivingForm">
              @csrf

              <div class="form-group">
                <label class="control-label">Ingredient <span class="text-danger">*</span></label>
                <select class="form-control @error('ingredient_id') is-invalid @enderror" name="ingredient_id" id="ingredientSelect" required>
                  <option value="">Select</option>
                  @foreach($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}" {{ (string) old('ingredient_id') === (string) $ingredient->id ? 'selected' : '' }}>
                      {{ $ingredient->name }}
                    </option>
                  @endforeach
                </select>
                @error('ingredient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group">
                <label class="control-label d-block">Receive as</label>
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                  <label class="btn btn-outline-primary active" id="modePackageLabel">
                    <input type="radio" name="receive_mode" value="package" {{ old('receive_mode', 'package') === 'package' ? 'checked' : '' }}> Package
                  </label>
                  <label class="btn btn-outline-primary" id="modeUsageLabel">
                    <input type="radio" name="receive_mode" value="usage" {{ old('receive_mode') === 'usage' ? 'checked' : '' }}> Usage unit
                  </label>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Quantity <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="number" class="form-control @error('quantity') is-invalid @enderror"
                        name="quantity" id="quantityInput"
                        value="{{ old('quantity') }}" required min="0.0001" step="any">
                      <div class="input-group-append">
                        <span class="input-group-text" id="quantityUnitLabel">—</span>
                      </div>
                    </div>
                    @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('received_at') is-invalid @enderror" name="received_at"
                      value="{{ old('received_at', now()->toDateString()) }}" required>
                    @error('received_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label class="control-label">Available stock</label>
                <div class="receiving-summary" id="stockPreview">Select an ingredient to see current stock.</div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Purchase cost <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <input type="number" class="form-control @error('purchase_cost') is-invalid @enderror"
                        name="purchase_cost" id="purchaseCostInput"
                        value="{{ old('purchase_cost') }}" required min="0" step="1" placeholder="Total amount paid">
                      <div class="input-group-append">
                        <span class="input-group-text">TZS</span>
                      </div>
                    </div>
                    <small class="text-muted">Total cost you paid to buy this stock.</small>
                    @error('purchase_cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Supplier</label>
                    <input type="text" class="form-control @error('supplier') is-invalid @enderror" name="supplier"
                      value="{{ old('supplier') }}" maxlength="255" placeholder="Optional">
                    @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="control-label">Notes</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2"
                  maxlength="1000">{{ old('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="tile-footer px-0 pb-0">
                <button class="btn btn-primary" type="submit" id="receivingSubmitBtn" disabled>
                  <i class="fa fa-check-circle"></i> Save
                </button>
                <a class="btn btn-secondary" href="{{ route('receivings.index') }}">Cancel</a>
              </div>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .receiving-summary {
      padding: 0.75rem 1rem;
      background: #f4f5f7;
      border: 1px solid #e8eaed;
      border-radius: 4px;
      font-weight: 600;
      color: #495057;
      line-height: 1.45;
    }

    .receiving-summary.is-ready {
      color: var(--brand, #7c461f);
    }

    .receiving-summary.is-empty {
      font-weight: 500;
      color: #6c757d;
      text-align: center;
    }

    .receiving-summary__after {
      margin-top: 0.35rem;
      font-size: 0.92rem;
      font-weight: 600;
      color: #28a745;
    }
  </style>
@endpush

@push('scripts')
  <script>
    window.ingredientReceivingOptions = @json($ingredientOptions);
  </script>
  <script src="{{ asset('panel-assets/js/receiving-form.js') }}"></script>
@endpush

@php
  $isEdit = $isEdit ?? false;
  $record = $ingredient ?? null;
  $packageUnitOptions = $packageUnits->map(fn ($unit) => [
      'id' => $unit->id,
      'name' => $unit->name,
      'symbol' => $unit->symbol,
  ])->values();
@endphp

@if($packageUnits->isEmpty())
  <div class="alert alert-warning mb-3">
    Add package units in <a href="{{ route('settings.index', ['tab' => 'packages']) }}">Settings</a> first.
  </div>
@endif

<div class="row ingredient-form-layout">
  <div class="col-lg-6">
    <div class="form-group">
      <label class="control-label">Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
        value="{{ old('name', $record->name ?? '') }}" required maxlength="255"
        placeholder="Eggs, Flour, Sugar">
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
      <label class="control-label">Brand</label>
      <input type="text" class="form-control @error('description') is-invalid @enderror" name="description"
        value="{{ old('description', $record->description ?? '') }}" maxlength="255"
        placeholder="Optional">
      @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
      <label class="control-label">Reorder level</label>
      <input type="number" class="form-control @error('reorder_level') is-invalid @enderror" name="reorder_level"
        value="{{ old('reorder_level', $record->reorder_level ?? 0) }}" min="0" step="any">
      @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group mb-0">
      <label class="mb-0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->is_active ?? true) ? 'checked' : '' }}>
        Active
      </label>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="ingredient-packaging-setup">
      <h5 class="ingredient-packaging-setup__title">Packaging</h5>

      <div class="form-group">
        <label class="control-label">Receiving</label>
        <div class="row">
          <div class="col-6">
            <select class="form-control @error('receiving_package_unit_id') is-invalid @enderror"
              name="receiving_package_unit_id" id="receivingPackageSelect" required>
              <option value="">Select</option>
              @foreach($packageUnits as $unit)
                <option value="{{ $unit->id }}" {{ (string) old('receiving_package_unit_id', $record->receiving_package_unit_id ?? '') === (string) $unit->id ? 'selected' : '' }}>
                  {{ $unit->name }}
                </option>
              @endforeach
            </select>
            @error('receiving_package_unit_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-6">
            <label class="control-label">
              <span id="usagePerReceivingLabelText">Qty in 1 unit</span> <span class="text-danger">*</span>
            </label>
            <input type="number" class="form-control @error('usage_per_receiving') is-invalid @enderror" name="usage_per_receiving"
              id="usagePerReceivingInput"
              value="{{ old('usage_per_receiving', $record->usage_per_receiving ?? '') }}" required min="0.0001" step="any">
            @error('usage_per_receiving')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      <div class="form-group mb-0">
        <label class="control-label">Usage</label>
        <select class="form-control @error('usage_package_unit_id') is-invalid @enderror"
          name="usage_package_unit_id" id="usagePackageSelect" required>
          <option value="">Select</option>
          @foreach($packageUnits as $unit)
            <option value="{{ $unit->id }}" {{ (string) old('usage_package_unit_id', $record->usage_package_unit_id ?? '') === (string) $unit->id ? 'selected' : '' }}>
              {{ $unit->name }}
            </option>
          @endforeach
        </select>
        @error('usage_package_unit_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      </div>

      <div class="ingredient-packaging-setup__summary" id="conversionSummary">—</div>
    </div>
  </div>
</div>

@once
  @push('styles')
    <style>
      .ingredient-form-layout .form-group {
        margin-bottom: 1rem;
      }

      .ingredient-packaging-setup {
        background: #f4f5f7;
        border: 1px solid #e8eaed;
        border-radius: 6px;
        padding: 1rem 1.25rem;
        height: 100%;
      }

      .ingredient-packaging-setup__title {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #2c3e50;
      }

      .ingredient-packaging-setup__summary {
        margin-top: 1rem;
        padding: 0.6rem 0.75rem;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #495057;
        text-align: center;
      }

      .ingredient-packaging-setup__summary.is-ready {
        color: var(--brand, #7c461f);
      }
    </style>
  @endpush

  @push('scripts')
    <script>
      window.ingredientPackageUnits = @json($packageUnitOptions);
    </script>
    <script src="{{ asset('panel-assets/js/ingredient-form.js') }}"></script>
  @endpush
@endonce

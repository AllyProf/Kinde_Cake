@php
  $isEdit = $isEdit ?? false;
  $record = $item ?? null;
@endphp

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Item name <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
        value="{{ old('name', $record->name ?? '') }}" required maxlength="255"
        placeholder="e.g. Chocolate Birthday Cake">
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Category <span class="text-danger">*</span></label>
      <select class="form-control @error('category_id') is-invalid @enderror" name="category_id" required>
        <option value="">Select category</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}" {{ (string) old('category_id', $record->category_id ?? '') === (string) $category->id ? 'selected' : '' }}>
            {{ $category->name }}
          </option>
        @endforeach
      </select>
      @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Package / unit <span class="text-danger">*</span></label>
      <select class="form-control @error('package_unit_id') is-invalid @enderror" name="package_unit_id" required>
        <option value="">Select unit</option>
        @foreach($packageUnits as $unit)
          <option value="{{ $unit->id }}" {{ (string) old('package_unit_id', $record->package_unit_id ?? '') === (string) $unit->id ? 'selected' : '' }}>
            {{ $unit->name }} ({{ $unit->symbol }})
          </option>
        @endforeach
      </select>
      @error('package_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6" id="itemPriceField">
    <div class="form-group">
      <label class="control-label">Price (TZS) <span class="text-danger js-price-required">*</span></label>
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text">TZS</span>
        </div>
        <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" id="itemPriceInput"
          value="{{ old('price', $record->price ?? '') }}" min="0" step="1"
          placeholder="e.g. 25000">
      </div>
      @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      <small class="text-muted">Price per selected unit (e.g. per kg, per piece)</small>
    </div>
  </div>
  <div class="col-md-6" id="itemSaleTypeField">
    <div class="form-group">
      <label class="control-label">Sale type <span class="text-danger">*</span></label>
      <select class="form-control @error('sale_type') is-invalid @enderror" name="sale_type" id="itemSaleTypeInput">
        @foreach(\App\Models\Item::saleTypeOptions() as $value => $label)
          <option value="{{ $value }}" @selected(old('sale_type', $record->sale_type ?? \App\Models\Item::SALE_TYPE_RETAIL) === $value)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('sale_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <small class="text-muted">Order items need a customer and pickup details on the sale form. Retail items are walk-in / quick pay.</small>
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">Description</label>
      <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3"
        maxlength="1000" placeholder="Optional notes about this item">{{ old('description', $record->description ?? '') }}</textarea>
      @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group">
      <label class="mb-0">
        <input type="checkbox" name="is_custom" id="itemIsCustom" value="1" {{ old('is_custom', $record->is_custom ?? false) ? 'checked' : '' }}>
        Custom order — name and price are entered on each sale
      </label>
      <small class="text-muted d-block">Use for custom cakes. Catalog name is the template (e.g. “Custom Cake”); staff set the real cake name and price when selling.</small>
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group mb-0">
      <label class="mb-0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->is_active ?? true) ? 'checked' : '' }}>
        Active — available for orders and sales
      </label>
    </div>
  </div>
</div>

@once
  @push('scripts')
    <script>
      (function () {
        var checkbox = document.getElementById('itemIsCustom');
        var priceInput = document.getElementById('itemPriceInput');
        var priceRequired = document.querySelector('.js-price-required');
        var saleTypeInput = document.getElementById('itemSaleTypeInput');
        if (!checkbox || !priceInput) {
          return;
        }

        function syncCustomMode() {
          var custom = checkbox.checked;
          priceInput.required = !custom;
          priceInput.disabled = custom;
          if (priceRequired) {
            priceRequired.classList.toggle('d-none', custom);
          }
          if (saleTypeInput) {
            saleTypeInput.required = !custom;
            saleTypeInput.disabled = custom;
            if (custom) {
              saleTypeInput.value = 'order';
            }
          }
        }

        checkbox.addEventListener('change', syncCustomMode);
        syncCustomMode();
      })();
    </script>
  @endpush
@endonce

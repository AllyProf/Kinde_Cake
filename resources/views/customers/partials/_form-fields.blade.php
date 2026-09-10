@php
  $record = $customer ?? null;
  $phoneFull = old('phone', $record->phone ?? '');
  $phoneLocal = '';
  if ($phoneFull) {
      $phoneLocal = preg_replace('/^\+255/', '', ltrim($phoneFull));
      $phoneLocal = preg_replace('/\D/', '', $phoneLocal);
  }
@endphp

<div class="row customer-location-fields">
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
        value="{{ old('name', $record->name ?? '') }}" required maxlength="255">
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">Phone</label>
      <div class="input-group tz-phone-group">
        <div class="input-group-prepend">
          <span class="input-group-text tz-phone-prefix">
            <img src="https://flagcdn.com/w20/tz.png" srcset="https://flagcdn.com/w40/tz.png 2x" width="20" height="15" alt="Tanzania">
            <span>+255</span>
          </span>
        </div>
        <input type="tel" class="form-control js-tz-phone-local @error('phone') is-invalid @enderror"
          data-phone-full="#customerPhoneFull"
          value="{{ $phoneLocal }}"
          placeholder="7XX XXX XXX"
          inputmode="numeric"
          maxlength="9"
          autocomplete="tel-national">
        <input type="hidden" name="phone" id="customerPhoneFull" value="{{ $phoneFull }}">
      </div>
      @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">Email</label>
      <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
        value="{{ old('email', $record->email ?? '') }}" maxlength="255">
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">Region</label>
      <select class="form-control js-customer-region @error('region') is-invalid @enderror" name="region" id="customerRegion">
        <option value=""></option>
        @foreach($regions as $region)
          <option value="{{ $region }}" @selected(old('region', $record->region ?? '') === $region)>{{ $region }}</option>
        @endforeach
      </select>
      @error('region')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">District</label>
      <select class="form-control js-customer-district @error('district') is-invalid @enderror" name="district" id="customerDistrict"
        data-selected="{{ old('district', $record->district ?? '') }}">
        <option value=""></option>
      </select>
      @error('district')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-12 col-md-6">
    <div class="form-group">
      <label class="control-label">Specific location</label>
      <input type="text" class="form-control @error('location') is-invalid @enderror" name="location"
        value="{{ old('location', $record->location ?? '') }}"
        placeholder="e.g. Mwenge, Sinza, near ABC shop"
        maxlength="255">
      @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
      <small class="text-muted">Street, ward, or landmark.</small>
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group">
      <label class="control-label">Notes</label>
      <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2">{{ old('notes', $record->notes ?? '') }}</textarea>
      @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group mb-0">
      <label class="mb-0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->is_active ?? true) ? 'checked' : '' }}>
        Active
      </label>
    </div>
  </div>
</div>

@once
  @push('styles')
    <style>
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

      .customer-location-fields .select2-container {
        width: 100% !important;
      }
    </style>
  @endpush

  @push('scripts')
    <script src="{{ asset('panel-assets/js/plugins/select2.min.js') }}"></script>
    <script src="{{ asset('panel-assets/js/phone-tz.js') }}"></script>
    <script>
      window.customerLocations = @json($customerLocations);
    </script>
    <script src="{{ asset('panel-assets/js/customer-location.js') }}"></script>
  @endpush
@endonce

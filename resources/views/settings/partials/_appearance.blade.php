<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="tile">
      <h3 class="tile-title">Appearance</h3>
      <div class="tile-body">
        <form action="{{ route('settings.appearance.update') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="form-group">
            <label class="control-label">Brand color <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
              <input type="color" class="form-control p-1 js-brand-color-picker"
                id="brandColorPicker"
                value="{{ old('brand_color', $brandColor) }}"
                style="width: 64px; height: 44px; cursor: pointer;">
              <input type="text" class="form-control @error('brand_color') is-invalid @enderror js-brand-color-text"
                name="brand_color"
                id="brandColorText"
                value="{{ old('brand_color', $brandColor) }}"
                placeholder="#7c461f"
                maxlength="7"
                required
                style="max-width: 140px;">
              <span class="badge badge-primary px-3 py-2 js-brand-color-preview">Preview</span>
            </div>
            @error('brand_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <small class="text-muted d-block mt-2">
              Used for header, buttons, sidebar accents, loader, and primary UI elements.
            </small>
          </div>

          <div class="tile-footer px-0 pb-0">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-check-circle"></i> Save Appearance
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4 col-lg-6">
    <div class="tile">
      <h3 class="tile-title">Live preview</h3>
      <div class="tile-body">
        <div class="p-3 rounded mb-3 js-preview-header" style="background: {{ $brandColor }}; color: var(--white);">
          <strong>{{ $business['name'] ?? config('app.name') }}</strong> header
        </div>
        <button type="button" class="btn btn-primary mb-2 js-preview-button">Primary button</button>
        <div class="widget-small primary coloured-icon js-preview-widget">
          <i class="icon fa fa-shopping-cart fa-3x"></i>
          <div class="info">
            <h4>Sample widget</h4>
            <p><b>123</b></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
  <script>
    (function () {
      var picker = document.getElementById('brandColorPicker');
      var text = document.getElementById('brandColorText');
      var previewBadge = document.querySelector('.js-brand-color-preview');
      var previewHeader = document.querySelector('.js-preview-header');
      if (!picker || !text) return;

      function normalizeHex(value) {
        if (!value) return null;
        value = value.trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) return value.toLowerCase();
        if (/^#[0-9A-Fa-f]{3}$/.test(value)) {
          return '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
        }
        return null;
      }

      function applyColor(color) {
        var normalized = normalizeHex(color);
        if (!normalized) return;
        picker.value = normalized;
        text.value = normalized;
        if (previewBadge) {
          previewBadge.style.backgroundColor = normalized;
          previewBadge.style.borderColor = normalized;
        }
        if (previewHeader) previewHeader.style.backgroundColor = normalized;
      }

      picker.addEventListener('input', function () { applyColor(picker.value); });
      text.addEventListener('input', function () { applyColor(text.value); });
      applyColor(text.value);
    })();
  </script>
@endpush

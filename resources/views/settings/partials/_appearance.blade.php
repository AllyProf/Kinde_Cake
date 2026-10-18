<div class="row">
  <div class="col-md-8 col-lg-6">
    <div class="tile">
      <h3 class="tile-title">Appearance</h3>
      <div class="tile-body">
        <form action="{{ route('settings.appearance.update') }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="form-group">
            <label class="control-label">Page background image</label>
            @if($panelBackgroundUrl)
              <div class="panel-bg-preview mb-2">
                <img src="{{ $panelBackgroundUrl }}" alt="Current page background" class="panel-bg-preview__image">
              </div>
              <div class="animated-checkbox mb-2">
                <label>
                  <input type="checkbox" name="remove_panel_background" value="1">
                  <span class="label-text">Remove current background image</span>
                </label>
              </div>
            @endif
            <div class="custom-file">
              <input type="file" class="custom-file-input @error('panel_background') is-invalid @enderror"
                id="panelBackgroundInput" name="panel_background" accept="image/jpeg,image/png,image/webp">
              <label class="custom-file-label" for="panelBackgroundInput">Choose image…</label>
            </div>
            @error('panel_background')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <label class="control-label mt-3" for="panelBackgroundDisplay">Background style</label>
            <select class="form-control" id="panelBackgroundDisplay" name="panel_background_display">
              <option value="repeat" @selected(old('panel_background_display', $panelBackgroundDisplay ?? 'repeat') === 'repeat')>
                Repeating pattern (shows between white cards)
              </option>
              <option value="cover" @selected(old('panel_background_display', $panelBackgroundDisplay ?? 'repeat') === 'cover')>
                Full-page photo (single image, cover)
              </option>
            </select>
            <small class="text-muted d-block mt-2">
              Applies to logged-in pages only (login keeps the default cake photo). Use a light watermark with <strong>Repeating pattern</strong>. JPG, PNG, or WebP, up to 4 MB.
            </small>
          </div>

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
        @include('partials._stat-card', [
          'label' => 'Sample stat card',
          'value' => '123',
          'class' => 'js-preview-stat-card',
        ])
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
      var previewStat = document.querySelector('.js-preview-stat-card');
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
        if (previewStat) previewStat.style.borderTopColor = normalized;
      }

      picker.addEventListener('input', function () { applyColor(picker.value); });
      text.addEventListener('input', function () { applyColor(text.value); });
      applyColor(text.value);

      var fileInput = document.getElementById('panelBackgroundInput');
      if (fileInput) {
        fileInput.addEventListener('change', function () {
          var label = fileInput.nextElementSibling;
          if (!label) return;
          label.textContent = fileInput.files && fileInput.files[0]
            ? fileInput.files[0].name
            : 'Choose image…';
        });
      }
    })();
  </script>
@endpush

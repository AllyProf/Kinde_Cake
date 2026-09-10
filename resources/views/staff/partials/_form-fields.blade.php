@php
  $isEdit = $isEdit ?? false;
  $member = $staff ?? null;
  $phoneFull = old('phone', $member->phone ?? '');
  $phoneLocal = '';
  if ($phoneFull) {
      $phoneLocal = preg_replace('/^\+255/', '', ltrim($phoneFull));
      $phoneLocal = preg_replace('/\D/', '', $phoneLocal);
  }
@endphp

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Full name <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
        value="{{ old('name', $member->name ?? '') }}" required>
      @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Email <span class="text-danger">*</span></label>
      <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
        value="{{ old('email', $member->email ?? '') }}" required>
      @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Phone</label>
      <div class="input-group staff-phone-group">
        <div class="input-group-prepend">
          <span class="input-group-text staff-phone-prefix">
            <img src="https://flagcdn.com/w20/tz.png" srcset="https://flagcdn.com/w40/tz.png 2x" width="20" height="15" alt="Tanzania">
            <span>+255</span>
          </span>
        </div>
        <input type="tel" class="form-control js-staff-phone-local @error('phone') is-invalid @enderror"
          id="staffPhoneLocal"
          value="{{ $phoneLocal }}"
          placeholder="7XX XXX XXX"
          inputmode="numeric"
          maxlength="9"
          autocomplete="tel-national">
        <input type="hidden" name="phone" id="staffPhoneFull" value="{{ $phoneFull }}">
      </div>
      @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
      <small class="text-muted">Tanzania number — enter digits after +255</small>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">Role <span class="text-danger">*</span></label>
      <select class="form-control @error('staff_role_id') is-invalid @enderror" name="staff_role_id" required>
        <option value="">Select role</option>
        @foreach($roles as $role)
          <option value="{{ $role->id }}" {{ (string) old('staff_role_id', $member->staff_role_id ?? '') === (string) $role->id ? 'selected' : '' }}>
            {{ $role->name }}
          </option>
        @endforeach
      </select>
      @error('staff_role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label d-flex justify-content-between align-items-center">
        <span>
          {{ $isEdit ? 'New password' : 'Password' }}
          @if(! $isEdit)
            <span class="text-danger">*</span>
          @endif
        </span>
        <button type="button" class="btn btn-sm btn-outline-primary js-generate-password">
          <i class="fa fa-magic"></i> Generate
        </button>
      </label>
      <div class="input-group">
        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
          id="staffPassword" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
        <div class="input-group-append">
          <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="#staffPassword" aria-label="Show password">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="password-strength mt-2" id="passwordStrength">
        <div class="password-strength__bar">
          <div class="password-strength__fill" id="passwordStrengthFill"></div>
        </div>
        <small class="password-strength__label text-muted" id="passwordStrengthLabel">Enter a password to check strength</small>
      </div>
      @if($isEdit)
        <small class="text-muted d-block mt-1">Leave blank to keep current password.</small>
      @endif
      @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <label class="control-label">
        Confirm password
        @if(! $isEdit)
          <span class="text-danger">*</span>
        @endif
      </label>
      <div class="input-group">
        <input type="password" class="form-control" name="password_confirmation" id="staffPasswordConfirm"
          {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
        <div class="input-group-append">
          <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="#staffPasswordConfirm" aria-label="Show confirm password">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-12">
    <div class="form-group mb-0">
      <label class="mb-0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $member->is_active ?? true) ? 'checked' : '' }}>
        Active — can sign in to the system
      </label>
    </div>
  </div>
</div>

@once
  @push('styles')
    <style>
      .staff-phone-prefix {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        background: #f8f9fa;
        border-color: #ced4da;
      }

      .staff-phone-prefix img {
        border-radius: 2px;
        box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
      }

      .staff-phone-group .form-control {
        border-left: 0;
      }

      .password-strength__bar {
        height: 6px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
      }

      .password-strength__fill {
        height: 100%;
        width: 0;
        border-radius: 4px;
        transition: width 0.2s ease, background-color 0.2s ease;
        background: #dc3545;
      }

      .password-strength__fill.is-weak { width: 25%; background: #dc3545; }
      .password-strength__fill.is-fair { width: 50%; background: #fd7e14; }
      .password-strength__fill.is-good { width: 75%; background: #ffc107; }
      .password-strength__fill.is-strong { width: 100%; background: #28a745; }

      .password-strength__label.is-weak { color: #dc3545 !important; }
      .password-strength__label.is-fair { color: #fd7e14 !important; }
      .password-strength__label.is-good { color: #d39e00 !important; }
      .password-strength__label.is-strong { color: #28a745 !important; }
    </style>
  @endpush

  @push('scripts')
    <script src="{{ asset('panel-assets/js/staff-form.js') }}"></script>
  @endpush
@endonce

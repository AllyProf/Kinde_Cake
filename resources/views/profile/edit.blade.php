@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
  @php
    $phoneFull = old('phone', $user->phone ?? '');
    $phoneLocal = '';
    if ($phoneFull) {
        $phoneLocal = preg_replace('/^\+255/', '', ltrim($phoneFull));
        $phoneLocal = preg_replace('/\D/', '', $phoneLocal);
    }
  @endphp

  <div class="app-title">
    <div>
      <h1><i class="fa fa-user"></i> My Profile</h1>
      <p>Update your account details and password</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Profile</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Account details</h3>
        <div class="tile-body">
          <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
            @method('PUT')

            <div class="profile-photo-section mb-4">
              <div class="profile-photo-preview" id="profilePhotoPreview">
                @if($user->hasPhoto())
                  <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}" class="profile-photo-preview__img" id="profilePhotoCurrent">
                @else
                  <div class="profile-photo-preview__initials" id="profilePhotoInitials">{{ $user->initials() }}</div>
                @endif
              </div>
              <div class="profile-photo-fields">
                <label class="control-label d-block" for="profilePhotoInput">Profile photo</label>
                <input type="file"
                  class="form-control-file @error('photo') is-invalid @enderror"
                  id="profilePhotoInput"
                  name="photo"
                  accept="image/jpeg,image/png,image/webp">
                <small class="text-muted d-block mt-1">JPG, PNG, or WebP. Max 2 MB.</small>
                @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @if($user->hasPhoto())
                  <div class="form-check mt-2">
                    <label class="form-check-label">
                      <input type="checkbox" class="form-check-input" name="remove_photo" value="1" id="profileRemovePhoto">
                      Remove current photo
                    </label>
                  </div>
                @endif
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Full name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name', $user->name) }}" required>
                  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Email @if($user->isOwner())<span class="text-danger">*</span>@endif</label>
                  @if($user->isOwner())
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                      value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  @else
                    <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                    <small class="text-muted">Contact the owner to change your email.</small>
                  @endif
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
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Role</label>
                  <input type="text" class="form-control" value="{{ $user->roleLabel() }}" disabled>
                </div>
              </div>
            </div>

            <hr>
            <h4 class="mb-3">Change password</h4>
            <p class="text-muted">Leave blank to keep your current password.</p>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label d-flex justify-content-between align-items-center">
                    <span>New password</span>
                    <button type="button" class="btn btn-sm btn-outline-primary js-generate-password">
                      <i class="fa fa-magic"></i> Generate
                    </button>
                  </label>
                  <div class="input-group">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                      id="staffPassword" autocomplete="new-password">
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
                  @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">Confirm new password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" name="password_confirmation" id="staffPasswordConfirm"
                      autocomplete="new-password">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="#staffPasswordConfirm" aria-label="Show confirm password">
                        <i class="fa fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tile-footer px-0 pb-0">
              <button class="btn btn-primary" type="submit"><i class="fa fa-check-circle"></i> Save changes</button>
              <a class="btn btn-secondary" href="{{ route('dashboard') }}">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

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

    .profile-photo-section {
      display: flex;
      align-items: center;
      gap: 1.25rem;
      padding: 1rem;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 6px;
    }

    .profile-photo-preview {
      flex: 0 0 auto;
    }

    .profile-photo-preview__img,
    .profile-photo-preview__initials {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      border: 3px solid #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .profile-photo-preview__img {
      object-fit: cover;
      display: block;
    }

    .profile-photo-preview__initials {
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--brand, #009688);
      color: #fff;
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .profile-photo-fields {
      flex: 1 1 auto;
      min-width: 0;
    }

    @media (max-width: 575.98px) {
      .profile-photo-section {
        flex-direction: column;
        align-items: flex-start;
      }

      .profile-photo-preview {
        align-self: center;
      }
    }
  </style>
@endpush

@push('scripts')
  <script src="{{ asset('panel-assets/js/staff-form.js') }}"></script>
  <script>
    (function () {
      var input = document.getElementById('profilePhotoInput');
      var preview = document.getElementById('profilePhotoPreview');
      var removeCheckbox = document.getElementById('profileRemovePhoto');
      var initials = @json($user->initials());

      if (!input || !preview) {
        return;
      }

      input.addEventListener('change', function () {
        if (removeCheckbox) {
          removeCheckbox.checked = false;
        }

        var file = input.files && input.files[0];
        if (!file) {
          return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
          preview.innerHTML =
            '<img src="' + event.target.result + '" alt="Profile photo preview" class="profile-photo-preview__img" id="profilePhotoCurrent">';
        };
        reader.readAsDataURL(file);
      });

      if (removeCheckbox) {
        removeCheckbox.addEventListener('change', function () {
          if (!removeCheckbox.checked) {
            return;
          }

          input.value = '';
          preview.innerHTML =
            '<div class="profile-photo-preview__initials" id="profilePhotoInitials">' + initials + '</div>';
        });
      }
    })();
  </script>
@endpush

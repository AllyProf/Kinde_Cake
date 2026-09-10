@extends('layouts.app')

@section('title', 'Staff')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-users"></i> Staff</h1>
      <p>Manage team members and their access</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Staff</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">All Staff</h3>
          <p>
            <a class="btn btn-primary icon-btn" href="{{ route('staff.create') }}">
              <i class="fa fa-plus"></i> Add Staff
            </a>
          </p>
        </div>
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($staff as $member)
                  <tr>
                    <td>{{ $staff->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $member->name }}</strong></td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->phone ?? '—' }}</td>
                    <td>{{ $member->staffRole?->name ?? '—' }}</td>
                    <td>
                      @if($member->is_active)
                        <span class="badge badge-success">Active</span>
                      @else
                        <span class="badge badge-secondary">Inactive</span>
                      @endif
                    </td>
                    <td>
                      @if($member->is_active)
                        <form action="{{ route('impersonate.start', $member) }}" method="POST" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-info" title="View app as this staff member">
                            <i class="fa fa-user-secret"></i> Impersonate
                          </button>
                        </form>
                      @endif
                      <a class="btn btn-sm btn-primary" href="{{ route('staff.edit', $member) }}">
                        <i class="fa fa-edit"></i> Edit
                      </a>
                      <button type="button" class="btn btn-sm btn-warning js-reset-password"
                        data-staff-id="{{ $member->id }}"
                        data-staff-name="{{ $member->name }}"
                        data-action="{{ route('staff.reset-password', $member) }}"
                        title="Set a new password for this staff member">
                        <i class="fa fa-key"></i> Reset password
                      </button>
                      <form action="{{ route('staff.destroy', $member) }}" method="POST" class="d-inline js-swal-delete">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" data-title="Remove staff?" data-text="This will remove {{ $member->name }} from your team.">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                      No staff yet. <a href="{{ route('staff.create') }}">Add your first team member</a>.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $staff->links() }}
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form id="resetPasswordForm" method="POST" action="">
          @csrf
          <input type="hidden" name="_reset_staff_id" id="resetStaffId" value="{{ old('_reset_staff_id') }}">
          <div class="modal-header">
            <h5 class="modal-title" id="resetPasswordModalLabel">Reset password</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="text-muted mb-3">Set a new password for <strong id="resetPasswordStaffName"></strong>.</p>
            <div class="form-group">
              <label class="control-label d-flex justify-content-between align-items-center">
                <span>New password <span class="text-danger">*</span></span>
                <button type="button" class="btn btn-sm btn-outline-primary js-generate-password">
                  <i class="fa fa-magic"></i> Generate
                </button>
              </label>
              <div class="input-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror" name="password"
                  id="staffPassword" required autocomplete="new-password">
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
            <div class="form-group mb-0">
              <label class="control-label">Confirm password <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="password_confirmation" id="staffPasswordConfirm"
                  required autocomplete="new-password">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="#staffPasswordConfirm" aria-label="Show confirm password">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning"><i class="fa fa-key"></i> Reset password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
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
  <script src="{{ asset('panel-assets/js/reset-password-modal.js') }}"></script>
@endpush

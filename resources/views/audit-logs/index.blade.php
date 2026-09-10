@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-history"></i> Audit Logs</h1>
      <p>Track sign-ins, staff changes, and other important actions</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Audit Logs</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Filters</h3>
        <div class="tile-body">
          <form method="GET" action="{{ route('audit-logs.index') }}" class="form-row">
            <div class="form-group col-md-3">
              <label class="control-label" for="filterAction">Action</label>
              <select class="form-control" id="filterAction" name="action">
                <option value="">All actions</option>
                @foreach($actions as $value => $label)
                  <option value="{{ $value }}" {{ ($filters['action'] ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="control-label" for="filterUser">User</label>
              <select class="form-control" id="filterUser" name="user_id">
                <option value="">All users</option>
                @foreach($users as $filterUser)
                  <option value="{{ $filterUser->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $filterUser->id ? 'selected' : '' }}>
                    {{ $filterUser->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-2">
              <label class="control-label" for="filterDateFrom">From</label>
              <input type="date" class="form-control" id="filterDateFrom" name="date_from"
                value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="form-group col-md-2">
              <label class="control-label" for="filterDateTo">To</label>
              <input type="date" class="form-control" id="filterDateTo" name="date_to"
                value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="form-group col-md-2">
              <label class="control-label" for="filterSearch">Search</label>
              <input type="text" class="form-control" id="filterSearch" name="q"
                value="{{ $filters['q'] ?? '' }}" placeholder="Description or IP">
            </div>
            <div class="form-group col-md-12 mb-0">
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Apply filters
              </button>
              <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary">Clear</a>
            </div>
          </form>
        </div>
      </div>

      <div class="tile">
        <h3 class="tile-title">Activity</h3>
        <div class="tile-body">
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Date & time</th>
                  <th>User</th>
                  <th>Action</th>
                  <th>Description</th>
                  <th>IP address</th>
                </tr>
              </thead>
              <tbody>
                @forelse($logs as $log)
                  <tr>
                    <td class="text-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td>
                      @php
                        $badgeClass = match ($log->action) {
                          \App\Models\AuditLog::ACTION_LOGIN_FAILED => 'badge-danger',
                          \App\Models\AuditLog::ACTION_LOGIN, \App\Models\AuditLog::ACTION_LOGOUT => 'badge-info',
                          \App\Models\AuditLog::ACTION_STAFF_DELETED => 'badge-danger',
                          \App\Models\AuditLog::ACTION_STAFF_PASSWORD_RESET, \App\Models\AuditLog::ACTION_PASSWORD_CHANGED => 'badge-warning',
                          \App\Models\AuditLog::ACTION_IMPERSONATION_STARTED, \App\Models\AuditLog::ACTION_IMPERSONATION_STOPPED => 'badge-dark',
                          default => 'badge-secondary',
                        };
                      @endphp
                      <span class="badge {{ $badgeClass }}">{{ $log->actionLabel() }}</span>
                    </td>
                    <td>
                      {{ $log->description }}
                      @if(! empty($log->properties))
                        <details class="mt-1">
                          <summary class="text-muted small">Details</summary>
                          <ul class="small mb-0 pl-3">
                            @foreach($log->properties as $key => $value)
                              <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</li>
                            @endforeach
                          </ul>
                        </details>
                      @endif
                    </td>
                    <td class="text-muted">{{ $log->ip_address ?? '—' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">No audit log entries found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $logs->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection

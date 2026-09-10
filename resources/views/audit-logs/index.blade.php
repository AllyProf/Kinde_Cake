@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
  @php
    $hasAuditFilters = filled($filters['action'] ?? '')
      || filled($filters['user_id'] ?? '')
      || filled($filters['date_from'] ?? '')
      || filled($filters['date_to'] ?? '')
      || filled($filters['q'] ?? '');
    $activeAuditFilterCount = collect([
      $filters['action'] ?? '',
      $filters['user_id'] ?? '',
      $filters['date_from'] ?? '',
      $filters['date_to'] ?? '',
      $filters['q'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
  @endphp

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
        <div class="tile-title-w-btn">
          <h3 class="title">Activity</h3>
          <p>
            <button type="button"
              class="btn btn-outline-primary icon-btn js-audit-logs-filter-toggle"
              data-toggle="collapse"
              data-target="#auditLogsFilters"
              aria-expanded="{{ $hasAuditFilters ? 'true' : 'false' }}"
              aria-controls="auditLogsFilters">
              <i class="fa fa-filter"></i> Filter
              @if($hasAuditFilters)
                <span class="badge badge-light ml-1">{{ $activeAuditFilterCount }}</span>
              @endif
            </button>
          </p>
        </div>
        <div class="tile-body">
          <div id="auditLogsFilters" class="collapse sales-filter-bar mb-3 {{ $hasAuditFilters ? 'show' : '' }}">
            <form method="GET" action="{{ route('audit-logs.index') }}" id="auditLogsFilterForm">
              <div class="row align-items-end sales-filter-row">
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="control-label" for="filterAction">Action</label>
                  <select class="form-control form-control-sm" id="filterAction" name="action">
                    <option value="">All actions</option>
                    @foreach($actions as $value => $label)
                      <option value="{{ $value }}" {{ ($filters['action'] ?? '') === $value ? 'selected' : '' }}>
                        {{ $label }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="control-label" for="filterUser">User</label>
                  <select class="form-control form-control-sm" id="filterUser" name="user_id">
                    <option value="">All users</option>
                    @foreach($users as $filterUser)
                      <option value="{{ $filterUser->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $filterUser->id ? 'selected' : '' }}>
                        {{ $filterUser->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                  <label class="control-label" for="filterDateFrom">From</label>
                  <input type="date" class="form-control form-control-sm" id="filterDateFrom" name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                  <label class="control-label" for="filterDateTo">To</label>
                  <input type="date" class="form-control form-control-sm" id="filterDateTo" name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-lg-2">
                  <label class="control-label" for="filterSearch">Search</label>
                  <input type="text" class="form-control form-control-sm" id="filterSearch" name="q"
                    value="{{ $filters['q'] ?? '' }}" placeholder="Description, IP, location">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa fa-filter"></i> Apply filters
                  </button>
                  <a href="{{ route('audit-logs.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                </div>
              </div>
            </form>
          </div>

          <div class="table-responsive audit-logs-list">
            <table class="table table-hover table-bordered detail-card-table audit-logs-list-table">
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
                    <td class="text-nowrap" data-label="Date & time">{{ $log->created_at->format('d M Y, H:i') }}</td>
                    <td data-label="User">{{ $log->user?->name ?? '—' }}</td>
                    <td data-label="Action">
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
                    <td data-label="Description">
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
                    <td data-label="IP address">
                      @if($log->ip_address)
                        <span class="d-block">{{ $log->ip_address }}</span>
                        @if($log->ip_location)
                          <small class="text-muted audit-log-ip-location">
                            <i class="fa fa-map-marker"></i> {{ $log->ip_location }}
                          </small>
                        @endif
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4" data-label="">No audit log entries found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($logs->total() > 0)
            <div class="audit-logs-pagination mt-3">
              <p class="text-muted small mb-2 mb-md-0">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
              </p>
              @if($logs->hasPages())
                {{ $logs->links('pagination::bootstrap-4') }}
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .sales-filter-bar {
      padding: 0.5rem 0.75rem 0.65rem;
      background: #f8f9fa;
      border-radius: 4px;
      border: 1px solid #e9ecef;
    }

    .sales-filter-bar .control-label {
      display: block;
      margin-bottom: 0.2rem;
      font-size: 0.78rem;
      font-weight: 600;
      color: #6c757d;
      line-height: 1.2;
    }

    .sales-filter-row > [class*="col-"] {
      margin-bottom: 0;
    }

    .audit-log-ip-location {
      display: block;
      margin-top: 0.15rem;
      line-height: 1.35;
    }

    .audit-logs-pagination {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }

    @media (min-width: 768px) {
      .audit-logs-pagination {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
      }

      .audit-logs-pagination .pagination {
        margin-bottom: 0;
      }
    }

    @media (max-width: 991.98px) {
      .sales-filter-row > [class*="col-"]:not(:last-child) {
        margin-bottom: 0.5rem;
      }
    }
  </style>
@endpush

@extends('layouts.app')

@section('title', 'Debt Management')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-credit-card"></i> Debt Management</h1>
      <p>Track partial payments, credit sales, and outstanding balances.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Debts</li>
    </ul>
  </div>

  <div class="row mb-3">
    <div class="col-md-4">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-list fa-3x"></i>
        <div class="info">
          <h4>Open debts</h4>
          <p><b>{{ number_format($stats['count']) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Total outstanding</h4>
          <p><b>{{ number_format($stats['total_outstanding'], 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="widget-small danger coloured-icon">
        <i class="icon fa fa-exclamation-triangle fa-3x"></i>
        <div class="info">
          <h4>Overdue</h4>
          <p><b>{{ number_format($stats['overdue_count']) }}</b></p>
        </div>
      </div>
    </div>
  </div>

  @php
    $hasDebtsFilters = filled($filters['search'] ?? '')
      || filled($filters['type'] ?? '')
      || filled($filters['due'] ?? '');
    $activeDebtsFilterCount = collect([
      $filters['search'] ?? '',
      $filters['type'] ?? '',
      $filters['due'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
  @endphp

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Outstanding debts</h3>
          <p>
            <button type="button"
              class="btn btn-outline-primary icon-btn js-debts-filter-toggle"
              data-toggle="collapse"
              data-target="#debtsFilters"
              aria-expanded="{{ $hasDebtsFilters ? 'true' : 'false' }}"
              aria-controls="debtsFilters">
              <i class="fa fa-filter"></i> Filter
              @if($hasDebtsFilters)
                <span class="badge badge-light ml-1">{{ $activeDebtsFilterCount }}</span>
              @endif
            </button>
            <a class="btn btn-outline-secondary icon-btn" href="{{ route('sales.index') }}">
              <i class="fa fa-shopping-cart"></i> All sales
            </a>
          </p>
        </div>
        <div class="tile-body">
          <div id="debtsFilters" class="collapse sales-filter-bar mb-3 {{ $hasDebtsFilters ? 'show' : '' }}">
            <form method="GET" action="{{ route('debts.index') }}" id="debtsFilterForm">
              <div class="row align-items-end sales-filter-row">
                <div class="col-12 col-lg-4 col-md-6">
                  <label class="control-label" for="debtsSearchInput">Search</label>
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="search" class="form-control form-control-sm" id="debtsSearchInput" name="search"
                      value="{{ $filters['search'] ?? '' }}"
                      placeholder="Sale no., customer, phone..."
                      autocomplete="off">
                  </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="debtsTypeFilter">Type</label>
                  <select class="form-control form-control-sm js-debts-filter" id="debtsTypeFilter" name="type">
                    <option value="">All types</option>
                    <option value="partial" @selected(($filters['type'] ?? '') === 'partial')>Partial</option>
                    <option value="credit" @selected(($filters['type'] ?? '') === 'credit')>Credit</option>
                    <option value="unpaid" @selected(($filters['type'] ?? '') === 'unpaid')>Unpaid</option>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="debtsDueFilter">Due date</label>
                  <select class="form-control form-control-sm js-debts-filter" id="debtsDueFilter" name="due">
                    <option value="">Any due date</option>
                    <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Overdue</option>
                    <option value="upcoming" @selected(($filters['due'] ?? '') === 'upcoming')>Upcoming</option>
                    <option value="none" @selected(($filters['due'] ?? '') === 'none')>No date set</option>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label d-none d-lg-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fa fa-search"></i> Apply
                  </button>
                </div>
                @if($hasDebtsFilters)
                  <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                    <label class="control-label d-none d-lg-block">&nbsp;</label>
                    <a href="{{ route('debts.index') }}" class="btn btn-outline-secondary btn-sm btn-block">
                      <i class="fa fa-times"></i> Clear
                    </a>
                  </div>
                @endif
              </div>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Sale No.</th>
                  <th>Customer</th>
                  <th>Type</th>
                  <th>Total</th>
                  <th>Paid</th>
                  <th>Outstanding</th>
                  <th>Due date</th>
                  <th>Status</th>
                  <th style="min-width:180px">Actions</th>
                </tr>
              </thead>
              <tbody id="debtsTableBody">
                @forelse($debts as $sale)
                  <tr class="js-debt-row {{ $sale->isDebtOverdue() ? 'debt-row-overdue' : '' }}"
                    data-search="{{ strtolower($sale->sale_number.' '.$sale->customer_name.' '.$sale->customer_phone) }}">
                    <td>{{ $debts->firstItem() + $loop->index }}</td>
                    <td>
                      <a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a>
                      <br><small class="text-muted">{{ $sale->sold_at->format('d M Y') }}</small>
                    </td>
                    <td>
                      {{ $sale->customer_name ?: '—' }}
                      @if($sale->customer_phone)
                        <br><small class="text-muted">{{ $sale->customer_phone }}</small>
                      @endif
                    </td>
                    <td><span class="badge {{ $sale->debtTypeBadgeClass() }}">{{ $sale->debtTypeLabel() }}</span></td>
                    <td>{{ $sale->formattedTotal() }}</td>
                    <td>{{ $sale->formattedAmountPaid() }}</td>
                    <td><strong class="text-danger">{{ $sale->formattedDebtAmount() }}</strong></td>
                    <td>
                      @if($sale->credit_repayment_date)
                        <span class="{{ $sale->isDebtOverdue() ? 'text-danger font-weight-bold' : '' }}">
                          {{ $sale->credit_repayment_date->format('d M Y') }}
                        </span>
                        @if($sale->isDebtOverdue())
                          <br><small class="text-danger">Overdue</small>
                        @endif
                      @else
                        —
                      @endif
                    </td>
                    <td><span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span></td>
                    <td>
                      <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-info mb-1">
                        <i class="fa fa-eye"></i> View
                      </a>
                      @if(auth()->user()->canPaySales() && $sale->canBePaid())
                        <button type="button"
                          class="btn btn-sm btn-success mb-1 js-pay-sale"
                          data-sale-number="{{ $sale->sale_number }}"
                          data-sale-total="{{ $sale->formattedTotal() }}"
                          data-sale-total-raw="{{ $sale->total }}"
                          data-amount-paid="{{ $sale->amount_paid }}"
                          data-balance-due="{{ $sale->balanceDue() }}"
                          data-pay-url="{{ route('sales.pay', $sale) }}"
                          data-customer-id="{{ $sale->customer_id }}"
                          data-customer-name="{{ $sale->customer_name }}"
                          data-customer-phone="{{ $sale->customer_phone }}"
                          data-repayment-date="{{ $sale->credit_repayment_date?->format('Y-m-d') }}">
                          <i class="fa fa-money"></i> {{ $sale->isPartiallyPaid() ? 'Pay balance' : 'Collect' }}
                        </button>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                      @if($hasDebtsFilters)
                        No debts match your filters.
                        <a href="{{ route('debts.index') }}">Clear filters</a>.
                      @else
                        No outstanding debts found.
                      @endif
                    </td>
                  </tr>
                @endforelse
                <tr id="debtsNoMatchRow" class="d-none">
                  <td colspan="10" class="text-center text-muted py-3">No debts match your search.</td>
                </tr>
              </tbody>
            </table>
          </div>
          {{ $debts->links() }}
        </div>
      </div>
    </div>
  </div>

  @if(auth()->user()->canPaySales())
    @include('sales.partials._pay-modal')
  @endif
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

    .pay-summary-box {
      padding: 0.75rem 1rem;
      background: #f8f9fa;
      border-radius: 4px;
      border: 1px solid #e9ecef;
      font-size: 0.92rem;
    }

    .pay-summary-box > div + div {
      margin-top: 0.35rem;
    }

    .debt-row-overdue {
      background-color: #fff5f5;
    }

    #payBalanceInfoGroup .select2-container {
      width: 100% !important;
    }

    @media (max-width: 991.98px) {
      .sales-filter-row > [class*="col-"]:not(:last-child) {
        margin-bottom: 0.5rem;
      }
    }
  </style>
@endpush

@push('scripts')
  <script>
    window.salesPaymentProviders = @json($paymentProviderOptions);
  </script>
  <script src="{{ asset('panel-assets/js/plugins/select2.min.js') }}"></script>
  <script src="{{ asset('panel-assets/js/sales-index.js') }}"></script>
@endpush

@extends('layouts.app')

@section('title', 'Sales')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-shopping-cart"></i> Sales</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Sales</li>
    </ul>
  </div>

  @php
    $hasSalesFilters = filled($filters['search'] ?? '')
      || filled($filters['status'] ?? '')
      || filled($filters['date_from'] ?? '')
      || filled($filters['date_to'] ?? '');
    $activeSalesFilterCount = collect([
      $filters['search'] ?? '',
      $filters['status'] ?? '',
      $filters['date_from'] ?? '',
      $filters['date_to'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
  @endphp

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">All Sales</h3>
          <p>
            <button type="button"
              class="btn btn-outline-primary icon-btn js-sales-filter-toggle"
              data-toggle="collapse"
              data-target="#salesFilters"
              aria-expanded="{{ $hasSalesFilters ? 'true' : 'false' }}"
              aria-controls="salesFilters">
              <i class="fa fa-filter"></i> Filter
              @if($hasSalesFilters)
                <span class="badge badge-light ml-1">{{ $activeSalesFilterCount }}</span>
              @endif
            </button>
            @if(auth()->user()->hasPermission('orders.create'))
              <a class="btn btn-primary icon-btn" href="{{ route('sales.create') }}">
                <i class="fa fa-plus"></i> New Sale
              </a>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <div id="salesFilters" class="collapse sales-filter-bar mb-3 {{ $hasSalesFilters ? 'show' : '' }}">
            <form method="GET" action="{{ route('sales.index') }}" id="salesFilterForm">
              <div class="row align-items-end sales-filter-row">
                <div class="col-12 col-lg-4 col-md-6">
                  <label class="control-label" for="salesSearchInput">Search</label>
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="search" class="form-control form-control-sm" id="salesSearchInput" name="search"
                      value="{{ $filters['search'] ?? '' }}"
                      placeholder="Sale no., customer, item, staff..."
                      autocomplete="off">
                  </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="salesStatusFilter">Status</label>
                  <select class="form-control form-control-sm js-sales-filter" id="salesStatusFilter" name="status">
                    @foreach(\App\Models\Sale::statusFilterOptions() as $value => $label)
                      <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="salesDateFrom">From</label>
                  <input type="date" class="form-control form-control-sm js-sales-filter" id="salesDateFrom" name="date_from"
                    value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="salesDateTo">To</label>
                  <input type="date" class="form-control form-control-sm js-sales-filter" id="salesDateTo" name="date_to"
                    value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label d-none d-lg-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fa fa-search"></i> Apply
                  </button>
                </div>
                @if($hasSalesFilters)
                  <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                    <label class="control-label d-none d-lg-block">&nbsp;</label>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm btn-block">
                      <i class="fa fa-times"></i> Clear
                    </a>
                  </div>
                @endif
              </div>
            </form>
          </div>

          <div class="table-responsive sales-list">
            <table class="table table-hover table-bordered sales-list-table">
              <thead>
                <tr>
                  <th class="d-none d-md-table-cell">#</th>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Items sold</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Sold by</th>
                  <th class="sales-list-table__actions-col">Actions</th>
                </tr>
              </thead>
              <tbody id="salesTableBody">
                @forelse($sales as $sale)
                  <tr class="js-sale-row {{ $sale->isVoided() ? 'sale-row-voided' : '' }}"
                    data-search="{{ strtolower($sale->sale_number.' '.$sale->customer_name.' '.$sale->customer_phone.' '.$sale->itemsSummary().' '.($sale->user?->name ?? '')) }}">
                    <td class="d-none d-md-table-cell" data-label="#">{{ $sales->firstItem() + $loop->index }}</td>
                    <td data-label="Date">{{ $sale->sold_at->format('d M Y H:i') }}</td>
                    <td data-label="Customer">
                      {{ $sale->customer_name ?: '—' }}
                      @if($sale->customer_phone)
                        <br><small class="text-muted">{{ $sale->customer_phone }}</small>
                      @endif
                    </td>
                    <td data-label="Items sold">
                      <span class="sale-items-summary">{{ $sale->itemsSummary() ?: '—' }}</span>
                    </td>
                    <td data-label="Total"><strong>{{ $sale->formattedTotal() }}</strong></td>
                    <td data-label="Status">
                      <span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span>
                      @if($sale->wasEdited())
                        <span class="badge badge-info">Edited</span>
                      @endif
                    </td>
                    <td data-label="Payment">{{ $sale->isPaid() || $sale->isPartiallyPaid() ? $sale->paymentSummaryLabel() : '—' }}</td>
                    <td data-label="Sold by">{{ $sale->user?->name ?? '—' }}</td>
                    <td class="sales-list-actions" data-label="Actions">
                      <a class="btn btn-sm btn-outline-primary mb-1" href="{{ route('sales.show', $sale) }}">
                        View More<br><small>{{ $sale->sale_number }}</small>
                      </a>

                      @if(auth()->user()->canPaySales() && $sale->canBePaid())
                          <button type="button"
                            class="btn btn-sm btn-success mb-1 js-pay-sale"
                            data-sale-id="{{ $sale->id }}"
                            data-sale-number="{{ $sale->sale_number }}"
                            data-sale-total="{{ $sale->formattedTotal() }}"
                            data-sale-total-raw="{{ $sale->total }}"
                            data-amount-paid="{{ $sale->amount_paid }}"
                            data-balance-due="{{ $sale->balanceDue() }}"
                            data-pay-url="{{ route('sales.pay', $sale) }}"
                            data-customer-id="{{ $sale->customer_id }}"
                            data-customer-name="{{ $sale->customer_name }}"
                            data-customer-phone="{{ $sale->customer_phone }}"
                            data-repayment-date="{{ $sale->credit_repayment_date?->format('Y-m-d') }}"
                            title="{{ $sale->isPartiallyPaid() ? 'Pay balance' : 'Pay' }}">
                            <i class="fa fa-money"></i>
                          </button>
                      @endif

                      @if(auth()->user()->hasPermission('orders.manage'))
                        <div class="btn-group d-inline-block">
                          <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-v"></i>
                          </button>
                          <div class="dropdown-menu dropdown-menu-right">
                            @if($sale->canBeEdited())
                              <a class="dropdown-item" href="{{ route('sales.edit', $sale) }}">
                                <i class="fa fa-edit"></i> Edit
                              </a>
                            @endif
                            @if($sale->isPending() && (float) $sale->amount_paid <= 0)
                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="js-swal-delete">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="dropdown-item text-danger"
                                data-title="Delete sale?"
                                data-text="Mark {{ $sale->sale_number }} as deleted? Ingredient stock will be restored. The sale will stay in this list.">
                                <i class="fa fa-trash"></i> Delete
                              </button>
                            </form>
                            @endif
                          </div>
                        </div>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr id="salesEmptyRow">
                    <td colspan="9" class="text-center text-muted py-4">
                      @if($hasSalesFilters)
                        No sales match your filters.
                        <a href="{{ route('sales.index') }}">Clear filters</a>.
                      @else
                        No sales yet.
                        @if(auth()->user()->hasPermission('orders.create'))
                          <a href="{{ route('sales.create') }}">Record a sale</a>.
                        @endif
                      @endif
                    </td>
                  </tr>
                @endforelse
                <tr id="salesNoMatchRow" class="d-none">
                  <td colspan="9" class="text-center text-muted py-4">No sales match your search on this page.</td>
                </tr>
              </tbody>
            </table>
          </div>
          {{ $sales->links() }}
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
    .sale-items-summary {
      display: inline-block;
      max-width: 260px;
      line-height: 1.35;
    }

    .sale-row-voided {
      opacity: 0.85;
      background-color: #f8f9fa;
    }

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

    #payBalanceInfoGroup .select2-container {
      width: 100% !important;
    }

    .sales-list-table__actions-col {
      min-width: 220px;
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

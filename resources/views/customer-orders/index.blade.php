@extends('layouts.app')

@section('title', 'Customer Orders')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-calendar-check-o"></i> Customer Orders</h1>
      <p>Orders with customers, collection time, and payment status</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Customer Orders</li>
    </ul>
  </div>

  @php
    $period = $filters['period'] ?? 'all';
    $hasFilters = filled($filters['search'] ?? '')
      || ($period !== 'all' && filled($period))
      || filled($filters['payment'] ?? '')
      || filled($filters['collect_from'] ?? '')
      || filled($filters['collect_to'] ?? '');
  @endphp

  <div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
      <div class="dashboard-stat-card">
        <div class="dashboard-stat-card__label">All orders</div>
        <div class="dashboard-stat-card__value">{{ number_format($summary['total']) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="dashboard-stat-card">
        <div class="dashboard-stat-card__label">Unpaid / partial</div>
        <div class="dashboard-stat-card__value">{{ number_format($summary['unpaid']) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="dashboard-stat-card">
        <div class="dashboard-stat-card__label">Collect today</div>
        <div class="dashboard-stat-card__value">{{ number_format($summary['collect_today']) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
      <div class="dashboard-stat-card">
        <div class="dashboard-stat-card__label">Upcoming pickups</div>
        <div class="dashboard-stat-card__value">{{ number_format($summary['upcoming']) }}</div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Orders</h3>
          <p>
            @if(auth()->user()->hasPermission('orders.create'))
              <a class="btn btn-primary icon-btn" href="{{ route('sales.create') }}">
                <i class="fa fa-plus"></i> New Sale
              </a>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <form method="GET" action="{{ route('customer-orders.index') }}" id="customerOrdersFilterForm" class="mb-3">
            <div class="customer-orders-period mb-3">
              @foreach([
                'all' => 'All',
                'today' => 'Collect today',
                'week' => 'This week',
                'month' => 'This month',
                'upcoming' => 'Upcoming',
                'overdue' => 'Overdue unpaid',
              ] as $value => $label)
                <a href="{{ route('customer-orders.index', array_filter(array_merge($filters, ['period' => $value]))) }}"
                  class="btn btn-sm {{ $period === $value ? 'btn-primary' : 'btn-outline-secondary' }} mb-1">
                  {{ $label }}
                </a>
              @endforeach
            </div>

            <div class="sales-filter-bar">
              <div class="row align-items-end sales-filter-row">
                <div class="col-12 col-lg-4 col-md-6">
                  <label class="control-label" for="customerOrdersSearchInput">Search</label>
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="search" class="form-control form-control-sm" id="customerOrdersSearchInput" name="search"
                      value="{{ $filters['search'] ?? '' }}"
                      placeholder="Order no., customer, phone, item..."
                      autocomplete="off">
                  </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="customerOrdersPaymentFilter">Payment</label>
                  <select class="form-control form-control-sm js-customer-orders-filter" id="customerOrdersPaymentFilter" name="payment">
                    <option value="" @selected(($filters['payment'] ?? '') === '')>All</option>
                    <option value="unpaid" @selected(($filters['payment'] ?? '') === 'unpaid')>Unpaid</option>
                    <option value="partial" @selected(($filters['payment'] ?? '') === 'partial')>Partial / advance</option>
                    <option value="paid" @selected(($filters['payment'] ?? '') === 'paid')>Paid</option>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="customerOrdersCollectFrom">Collect from</label>
                  <input type="date" class="form-control form-control-sm js-customer-orders-filter" id="customerOrdersCollectFrom" name="collect_from"
                    value="{{ $filters['collect_from'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="customerOrdersCollectTo">Collect to</label>
                  <input type="date" class="form-control form-control-sm js-customer-orders-filter" id="customerOrdersCollectTo" name="collect_to"
                    value="{{ $filters['collect_to'] ?? '' }}">
                </div>
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label d-none d-lg-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fa fa-search"></i> Apply
                  </button>
                </div>
                @if($hasFilters)
                  <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                    <label class="control-label d-none d-lg-block">&nbsp;</label>
                    <a href="{{ route('customer-orders.index') }}" class="btn btn-outline-secondary btn-sm btn-block">
                      <i class="fa fa-times"></i> Clear
                    </a>
                  </div>
                @endif
              </div>
            </div>
          </form>

          <div class="table-responsive sales-list">
            <table class="table table-hover table-bordered sales-list-table detail-card-table">
              <thead>
                <tr>
                  <th class="d-none d-md-table-cell">#</th>
                  <th>Customer</th>
                  <th>Order</th>
                  <th>Ordered by</th>
                  <th>Collect / ready</th>
                  <th>Total</th>
                  <th>Payment</th>
                  <th class="sales-list-table__actions-col">Actions</th>
                </tr>
              </thead>
              <tbody id="customerOrdersTableBody">
                @forelse($orders as $sale)
                  @php
                    $isOverdue = $sale->ready_at
                      && $sale->ready_at->isPast()
                      && $sale->hasOutstandingBalance();
                    $isToday = $sale->ready_at && $sale->ready_at->isToday();
                  @endphp
                  <tr class="js-customer-order-row {{ $isOverdue ? 'customer-order-row--overdue' : '' }} {{ $isToday ? 'customer-order-row--today' : '' }}"
                    data-search="{{ strtolower($sale->sale_number.' '.$sale->customer_name.' '.$sale->customer_phone.' '.$sale->itemsSummary().' '.($sale->notes ?? '').' '.($sale->assignedBy?->name ?? '').' '.($sale->user?->name ?? '')) }}">
                    <td class="d-none d-md-table-cell" data-label="#">{{ $orders->firstItem() + $loop->index }}</td>
                    <td data-label="Customer">
                      <strong>{{ $sale->customer_name }}</strong>
                      @if($sale->customer_phone)
                        <br><small class="text-muted">{{ $sale->customer_phone }}</small>
                      @endif
                    </td>
                    <td data-label="Order">
                      <a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a>
                      <br><span class="sale-items-summary">{{ $sale->itemsSummary() ?: '—' }}</span>
                      <br><small class="text-muted">Ordered {{ $sale->sold_at->format('d M Y H:i') }}</small>
                      @if($sale->isCakePointWorkOrder())
                        <br><span class="badge {{ $sale->cakePointStatusBadgeClass() }}">{{ $sale->cakePointStatusLabel() }}</span>
                      @endif
                    </td>
                    <td data-label="Ordered by">
                      <strong>{{ $sale->assignedBy?->name ?? $sale->user?->name ?? '—' }}</strong>
                      @if($sale->assignedTo)
                        <br><small class="text-muted">Staff: {{ $sale->assignedTo->name }}</small>
                      @endif
                    </td>
                    <td data-label="Collect / ready">
                      @if($sale->ready_at)
                        <strong>{{ $sale->ready_at->format('d M Y') }}</strong>
                        <br><small>{{ $sale->ready_at->format('H:i') }}</small>
                        <br><span class="badge {{ $sale->readyAtRelativeBadgeClass() }}">{{ $sale->readyAtRelativeLabel() }}</span>
                        @if($sale->ready_sms_sent_at)
                          <br><small class="text-success"><i class="fa fa-check"></i> Ready SMS {{ $sale->ready_sms_sent_at->format('d M H:i') }}</small>
                        @endif
                      @else
                        <span class="text-muted">Not set</span>
                      @endif
                    </td>
                    <td data-label="Total">
                      <strong>{{ $sale->formattedTotal() }}</strong>
                      @if($sale->hasOutstandingBalance())
                        <br><small class="text-danger">Due {{ $sale->formattedBalanceDue() }}</small>
                      @endif
                    </td>
                    <td data-label="Payment">
                      <span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span>
                      @if($sale->isPaid() || $sale->isPartiallyPaid())
                        <br><small>{{ $sale->paymentSummaryLabel() }}</small>
                      @endif
                    </td>
                    <td class="sales-list-actions" data-label="Actions">
                      <div class="btn-group">
                        <button type="button"
                          class="btn btn-sm btn-secondary dropdown-toggle"
                          data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false"
                          title="Actions">
                          <i class="fa fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                          <a class="dropdown-item" href="{{ route('sales.show', $sale) }}">
                            <i class="fa fa-eye"></i> View
                          </a>
                          @if(auth()->user()->canPaySales() && $sale->canBePaid())
                            <button type="button"
                              class="dropdown-item js-pay-sale"
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
                              data-repayment-date="{{ $sale->credit_repayment_date?->format('Y-m-d') }}">
                              <i class="fa fa-money"></i> {{ $sale->isPartiallyPaid() ? 'Pay balance' : 'Pay' }}
                            </button>
                          @endif
                          @if(($smsReady ?? false) && filled($sale->customer_phone) && auth()->user()->hasPermission('sms.send'))
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('customer-orders.notify-ready', $sale) }}" method="POST" class="js-swal-confirm">
                              @csrf
                              <button type="submit"
                                class="dropdown-item"
                                data-title="Send ready SMS?"
                                data-text="Notify {{ $sale->customer_name }} that order {{ $sale->sale_number }} is ready for collection?"
                                data-confirm="{{ $sale->ready_sms_sent_at ? 'Send again' : 'Send SMS' }}"
                                data-cancel="Cancel">
                                <i class="fa fa-commenting"></i>
                                {{ $sale->ready_sms_sent_at ? 'Resend ready SMS' : 'Ready SMS' }}
                              </button>
                            </form>
                          @endif
                        </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr id="customerOrdersEmptyRow">
                    <td colspan="8" class="text-center text-muted py-4">
                      @if($hasFilters)
                        No customer orders match your filters.
                        <a href="{{ route('customer-orders.index') }}">Clear filters</a>.
                      @else
                        No customer orders yet.
                        @if(auth()->user()->hasPermission('orders.create'))
                          <a href="{{ route('sales.create') }}">Create a sale</a>.
                        @endif
                      @endif
                    </td>
                  </tr>
                @endforelse
                <tr id="customerOrdersNoMatchRow" class="d-none">
                  <td colspan="8" class="text-center text-muted py-4">No orders match your search on this page.</td>
                </tr>
              </tbody>
            </table>
          </div>
          {{ $orders->links() }}
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
      max-width: 280px;
      line-height: 1.35;
    }

    .customer-orders-period .btn {
      margin-right: 0.25rem;
    }

    .customer-order-row--today {
      background: #f0f7ff;
    }

    .customer-order-row--overdue {
      background: #fff5f5;
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
      width: 70px;
      min-width: 70px;
    }

    .sales-list-actions .dropdown-item {
      cursor: pointer;
    }

    .sales-list-actions .dropdown-item i {
      width: 16px;
      margin-right: 6px;
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
  <script>
    (function ($) {
      var searchTimer = null;

      function filterRowsInstantly(term) {
        term = (term || '').toLowerCase().trim();
        var visible = 0;
        var hasServerRows = false;

        $('#customerOrdersTableBody .js-customer-order-row').each(function () {
          hasServerRows = true;
          var haystack = ($(this).data('search') || '').toString();
          var show = !term || haystack.indexOf(term) !== -1;
          $(this).toggle(show);
          if (show) visible++;
        });

        $('#customerOrdersNoMatchRow').toggleClass('d-none', !hasServerRows || visible > 0 || !term);
      }

      function submitFilters($form) {
        if (window.history && window.history.replaceState) {
          var params = $form.serialize();
          window.history.replaceState(null, '', $form.attr('action') + (params ? '?' + params : ''));
        }
        $form.trigger('submit');
      }

      $(function () {
        var $form = $('#customerOrdersFilterForm');
        var $search = $('#customerOrdersSearchInput');

        $search.on('input', function () {
          var value = $(this).val();
          filterRowsInstantly(value);
          clearTimeout(searchTimer);
          searchTimer = setTimeout(function () {
            submitFilters($form);
          }, 400);
        });

        $('.js-customer-orders-filter').on('change', function () {
          submitFilters($form);
        });

        $form.on('submit', function () {
          clearTimeout(searchTimer);
        });
      });
    })(jQuery);
  </script>
@endpush

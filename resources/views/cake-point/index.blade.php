@extends('layouts.app')

@section('title', 'Orders')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-birthday-cake"></i> Orders</h1>
      <p>
        @if(auth()->user()->isOwner())
          Create a new order and send it straight to staff.
        @else
          Orders assigned to you for preparation.
        @endif
      </p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Orders</li>
    </ul>
  </div>

  @if(auth()->user()->isOwner())
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="tile">
          <h3 class="tile-title"><i class="fa fa-plus-circle"></i> New order</h3>
          <div class="tile-body">
            @if($items->isEmpty())
              <div class="alert alert-warning mb-0">
                No cakes/items registered yet. <a href="{{ route('items.create') }}">Add items</a> first.
              </div>
            @elseif($staffMembers->isEmpty())
              <div class="alert alert-warning mb-0">
                No active staff members. <a href="{{ route('staff.create') }}">Add staff</a> first.
              </div>
            @else
              <form action="{{ route('cake-point.store') }}" method="POST" id="cakePointOrderForm">
                @csrf
                <div class="row">
                  <div class="col-12 col-lg-4 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointStaffId">Send to staff <span class="text-danger">*</span></label>
                      <select class="form-control @error('staff_id') is-invalid @enderror" id="cakePointStaffId" name="staff_id" required>
                        <option value="">Select staff...</option>
                        @foreach($staffMembers as $staff)
                          <option value="{{ $staff->id }}" @selected(old('staff_id') == $staff->id)>
                            {{ $staff->name }}{{ $staff->phone ? ' · '.$staff->phone : '' }}
                          </option>
                        @endforeach
                      </select>
                      @error('staff_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 col-lg-4 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointItemId">Cake / item <span class="text-danger">*</span></label>
                      <select class="form-control @error('item_id') is-invalid @enderror" id="cakePointItemId" name="item_id" required>
                        <option value="">Select cake...</option>
                        @foreach($items as $item)
                          <option value="{{ $item->id }}" data-price="{{ $item->price }}" @selected(old('item_id') == $item->id)>
                            {{ $item->name }} · {{ $item->formattedPrice() }}
                          </option>
                        @endforeach
                      </select>
                      @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-3 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointQuantity">Quantity <span class="text-danger">*</span></label>
                      <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="cakePointQuantity"
                        name="quantity" min="1" step="1" value="{{ old('quantity', 1) }}" required>
                      @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 col-sm-6 col-lg-3 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointDiscount">Discount (TZS)</label>
                      <input type="number" class="form-control @error('discount') is-invalid @enderror" id="cakePointDiscount"
                        name="discount" min="0" step="1" value="{{ old('discount', 0) }}" placeholder="0">
                      @error('discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      <small class="text-muted" id="cakePointDiscountHint">Applied to the selected item line.</small>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-lg-4 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointCustomerSelect">Customer</label>
                      <select class="form-control" id="cakePointCustomerSelect" name="customer_id">
                        <option value="">New / walk-in</option>
                        @foreach($customers as $customer)
                          <option value="{{ $customer->id }}"
                            data-name="{{ $customer->name }}"
                            data-phone="{{ $customer->phone }}"
                            @selected(old('customer_id') == $customer->id)>
                            {{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col-12 col-lg-4 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointCustomerName">Customer name</label>
                      <input type="text" class="form-control @error('customer_name') is-invalid @enderror" id="cakePointCustomerName"
                        name="customer_name" maxlength="255" value="{{ old('customer_name') }}" placeholder="Optional">
                      @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 col-lg-4 col-md-6">
                    <div class="form-group">
                      <label class="control-label" for="cakePointCustomerPhoneLocal">Phone <span class="text-danger">*</span></label>
                      <div class="input-group tz-phone-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text tz-phone-prefix">
                            <img src="https://flagcdn.com/w20/tz.png" srcset="https://flagcdn.com/w40/tz.png 2x" width="20" height="15" alt="Tanzania">
                            <span>+255</span>
                          </span>
                        </div>
                        <input type="tel" class="form-control js-tz-phone-local @error('customer_phone') is-invalid @enderror"
                          id="cakePointCustomerPhoneLocal"
                          data-phone-full="#cakePointCustomerPhoneFull"
                          placeholder="7XX XXX XXX"
                          inputmode="numeric"
                          maxlength="9"
                          autocomplete="tel-national"
                          required>
                        <input type="hidden" name="customer_phone" id="cakePointCustomerPhoneFull" value="{{ old('customer_phone') }}">
                      </div>
                      @error('customer_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-lg-8">
                    <div class="form-group">
                      <label class="control-label" for="cakePointNotes">Notes</label>
                      <textarea class="form-control @error('notes') is-invalid @enderror" id="cakePointNotes" name="notes"
                        rows="2" placeholder="Size, flavor, pickup time, special instructions...">{{ old('notes') }}</textarea>
                      @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-12 col-lg-4 d-flex align-items-center">
                    <div class="form-group mb-lg-0">
                      <div class="form-check">
                        <label class="form-check-label">
                          <input type="checkbox" class="form-check-input" name="send_sms" value="1"
                            @checked(old('send_sms', $smsReady)) @disabled(! $smsReady)>
                          Notify staff by SMS
                        </label>
                      </div>
                      @unless($smsReady)
                        <small class="text-muted">Configure SMS in Settings to enable phone notifications.</small>
                      @endunless
                    </div>
                  </div>
                </div>

                <div class="cake-point-order-summary mb-3" id="cakePointOrderSummary">
                  <span id="cakePointSubtotalLine" class="d-none text-muted mr-3"></span>
                  <span id="cakePointDiscountLine" class="d-none text-muted mr-3"></span>
                  Order total: <strong id="cakePointOrderTotal">—</strong>
                </div>

                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-paper-plane"></i> Send order
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  @php
    $orderListQuery = request()->only(['search', 'date_from', 'date_to']);
    $hasOrderFilters = filled($filters['search'] ?? '')
      || filled($filters['date_from'] ?? '')
      || filled($filters['date_to'] ?? '');
    $activeOrderFilterCount = collect([
      $filters['search'] ?? '',
      $filters['date_from'] ?? '',
      $filters['date_to'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
  @endphp

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Orders</h3>
          <p>
            <button type="button"
              class="btn btn-outline-primary icon-btn js-cake-point-filter-toggle"
              data-toggle="collapse"
              data-target="#cakePointFilters"
              aria-expanded="{{ $hasOrderFilters ? 'true' : 'false' }}"
              aria-controls="cakePointFilters">
              <i class="fa fa-filter"></i> Filter
              @if($hasOrderFilters)
                <span class="badge badge-light ml-1">{{ $activeOrderFilterCount }}</span>
              @endif
            </button>
            <a class="btn btn-outline-secondary icon-btn" href="{{ route('sales.index') }}">
              <i class="fa fa-shopping-cart"></i> All sales
            </a>
          </p>
        </div>
        <div class="tile-body">
          @if(auth()->user()->isOwner())
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item">
                <a class="nav-link {{ ($filters['view'] ?? 'assigned') === 'assigned' ? 'active' : '' }}"
                  href="{{ route('cake-point.index', array_merge($orderListQuery, ['view' => 'assigned'])) }}">
                  Assigned
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ ($filters['view'] ?? '') === 'all' ? 'active' : '' }}"
                  href="{{ route('cake-point.index', array_merge($orderListQuery, ['view' => 'all'])) }}">
                  All
                </a>
              </li>
            </ul>
          @endif

          <div id="cakePointFilters" class="collapse sales-filter-bar mb-3 {{ $hasOrderFilters ? 'show' : '' }}">
            <form method="GET" action="{{ route('cake-point.index') }}" id="cakePointFilterForm">
              @if(auth()->user()->isOwner())
                <input type="hidden" name="view" value="{{ $filters['view'] ?? 'assigned' }}">
              @endif
              <div class="row align-items-end sales-filter-row">
                <div class="col-12 col-lg-4 col-md-6">
                  <label class="control-label" for="cakePointSearchInput">Search</label>
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="search" class="form-control form-control-sm" id="cakePointSearchInput" name="search"
                      value="{{ $filters['search'] ?? '' }}"
                      placeholder="Sale no., customer, item, staff..."
                      autocomplete="off">
                  </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="cakePointDateFrom">From</label>
                  <input type="date" class="form-control form-control-sm js-cake-point-filter" id="cakePointDateFrom"
                    name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label" for="cakePointDateTo">To</label>
                  <input type="date" class="form-control form-control-sm js-cake-point-filter" id="cakePointDateTo"
                    name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                  <label class="control-label d-none d-lg-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fa fa-search"></i> Apply
                  </button>
                </div>
                @if($hasOrderFilters)
                  <div class="col-12 col-sm-6 col-lg-2 col-md-3">
                    <label class="control-label d-none d-lg-block">&nbsp;</label>
                    <a href="{{ route('cake-point.index', auth()->user()->isOwner() ? ['view' => $filters['view'] ?? 'assigned'] : []) }}"
                      class="btn btn-outline-secondary btn-sm btn-block">
                      <i class="fa fa-times"></i> Clear
                    </a>
                  </div>
                @endif
              </div>
            </form>
          </div>

          <div class="table-responsive cake-point-list">
            <table class="table table-hover table-bordered mb-0 cake-point-table detail-card-table">
              <thead>
                <tr>
                  <th>Sale</th>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Payment</th>
                  <th>Cake point</th>
                  <th>Sold by</th>
                  <th>Assigned to</th>
                  <th class="text-center cake-point-table__actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($orders as $order)
                  <tr class="{{ $order->isCakePointSent() ? 'cake-point-row--new' : '' }}">
                    <td data-label="Sale">
                      <a href="{{ route('sales.show', $order) }}">{{ $order->sale_number }}</a>
                    </td>
                    <td data-label="Date">{{ $order->sold_at->format('d M Y H:i') }}</td>
                    <td data-label="Customer">
                      {{ $order->customer_name ?: 'Walk-in' }}
                      @if($order->customer_phone)
                        <br><small class="text-muted">{{ $order->customer_phone }}</small>
                      @endif
                    </td>
                    <td data-label="Items"><small>{{ $order->itemsSummary() ?: '—' }}</small></td>
                    <td data-label="Total">{{ $order->formattedTotal() }}</td>
                    <td data-label="Payment">
                      @php($paymentSale = $order->isCakePointCompleted() ? $order->convertedToSale : $order)
                      @if($paymentSale)
                        <span class="badge {{ $paymentSale->statusBadgeClass() }}">{{ $paymentSale->statusLabel() }}</span>
                        @if($paymentSale->hasPaymentRecorded() && $paymentSale->paidByName())
                          <br><small class="text-muted">Paid by {{ $paymentSale->paidByName() }}</small>
                        @endif
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td data-label="Cake point">
                      @if($order->isCakePointOrder())
                        <span class="badge {{ $order->cakePointStatusBadgeClass() }}">{{ $order->cakePointStatusLabel() }}</span>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td data-label="Sold by">{{ $order->user?->name ?? '—' }}</td>
                    <td data-label="Assigned to">
                      @if($order->isAssigned())
                        {{ $order->assignedTo?->name ?? '—' }}
                        @if($order->assigned_at)
                          <br><small class="text-muted">{{ $order->assigned_at->format('d M Y H:i') }}</small>
                        @endif
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="cake-point-actions text-center" data-label="Actions">
                      @if($order->isCakePointSent() && $order->canUpdateCakePointStatus(auth()->user()))
                        <form action="{{ route('cake-point.update-status', $order) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="status" value="{{ \App\Models\Sale::CAKE_POINT_RECEIVED }}">
                          <button type="submit" class="btn btn-sm btn-info" title="Mark as received">
                            <i class="fa fa-inbox"></i> Received
                          </button>
                        </form>
                      @elseif($order->isCakePointReceived() && $order->canUpdateCakePointStatus(auth()->user()))
                        <form action="{{ route('cake-point.update-status', $order) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="status" value="{{ \App\Models\Sale::CAKE_POINT_PREPARED }}">
                          <button type="submit" class="btn btn-sm btn-primary" title="Mark as prepared">
                            <i class="fa fa-check"></i> Prepared
                          </button>
                        </form>
                      @elseif($order->isCakePointPrepared() && $order->canConvertToSale(auth()->user()) && auth()->user()->hasPermission('orders.create'))
                        <a href="{{ route('sales.create', ['from' => $order->id]) }}" class="btn btn-sm btn-success" title="Create sale with this order">
                          <i class="fa fa-shopping-cart"></i> Create sale
                        </a>
                      @elseif($order->isCakePointCompleted())
                        @php($finalSale = $order->convertedToSale)
                        @if($finalSale)
                          @if(auth()->user()->canPaySales() && $finalSale->canBePaid())
                            <button type="button"
                              class="btn btn-sm btn-success js-pay-sale"
                              data-sale-id="{{ $finalSale->id }}"
                              data-sale-number="{{ $finalSale->sale_number }}"
                              data-sale-total="{{ $finalSale->formattedTotal() }}"
                              data-sale-total-raw="{{ $finalSale->total }}"
                              data-amount-paid="{{ $finalSale->amount_paid }}"
                              data-balance-due="{{ $finalSale->balanceDue() }}"
                              data-pay-url="{{ route('sales.pay', $finalSale) }}"
                              data-customer-id="{{ $finalSale->customer_id }}"
                              data-customer-name="{{ $finalSale->customer_name }}"
                              data-customer-phone="{{ $finalSale->customer_phone }}"
                              data-repayment-date="{{ $finalSale->credit_repayment_date?->format('Y-m-d') }}"
                              title="{{ $finalSale->isPartiallyPaid() ? 'Pay balance' : 'Pay' }}">
                              <i class="fa fa-money"></i> Pay
                            </button>
                          @endif
                          <a href="{{ route('sales.show', $finalSale) }}" class="btn btn-sm btn-outline-success" title="View sale">
                            <i class="fa fa-external-link"></i>
                          </a>
                        @else
                          <span class="text-muted small">Completed</span>
                        @endif
                      @elseif(auth()->user()->isOwner() && $order->isCakePointOrder() && ! $order->isCakePointCompleted())
                        <span class="text-muted small">
                          @if($order->isCakePointSent())
                            Waiting for {{ $order->assignedTo?->name ?? 'staff' }} to receive
                          @elseif($order->isCakePointReceived())
                            {{ $order->assignedTo?->name ?? 'Staff' }} is preparing
                          @elseif($order->isCakePointPrepared())
                            Ready — staff will create sale
                          @endif
                        </span>
                      @endif

                      @if(auth()->user()->isOwner() && $order->isAssigned() && $staffMembers->isNotEmpty() && ! $order->isCakePointCompleted())
                        <button type="button" class="btn btn-sm btn-outline-secondary js-assign-cake-point"
                          data-toggle="modal" data-target="#assignModal"
                          data-sale-number="{{ $order->sale_number }}"
                          data-assign-url="{{ route('sales.assign', $order) }}"
                          data-current-staff="{{ $order->assigned_to_user_id }}"
                          title="Reassign order">
                          <i class="fa fa-exchange"></i>
                        </button>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                      @if($hasOrderFilters)
                        No orders match your filters.
                        <a href="{{ route('cake-point.index', auth()->user()->isOwner() ? ['view' => $filters['view'] ?? 'assigned'] : []) }}">Clear filters</a>.
                      @elseif(auth()->user()->isOwner())
                        No orders in this view. Use the form above to send a new order.
                      @else
                        No orders assigned to you yet.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($orders->hasPages())
            <div class="mt-3">
              {{ $orders->links() }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if(auth()->user()->isOwner() && $staffMembers->isNotEmpty())
    <div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form id="assignCakePointForm" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="assignModalLabel">Assign order</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p class="text-muted mb-3">
                Assign order <strong id="assignSaleNumber">—</strong> to a staff member.
              </p>
              <div class="form-group">
                <label class="control-label" for="assignStaffId">Staff member</label>
                <select class="form-control" id="assignStaffId" name="staff_id" required>
                  <option value="">Select staff...</option>
                  @foreach($staffMembers as $staff)
                    <option value="{{ $staff->id }}">
                      {{ $staff->name }}{{ $staff->phone ? ' · '.$staff->phone : ' · no phone' }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group mb-0">
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="send_sms" value="1" checked>
                    Notify staff by SMS
                  </label>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-paper-plane"></i> Assign order
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  @if(auth()->user()->canPaySales())
    @include('sales.partials._pay-modal')
  @endif
@endsection

@push('styles')
  <style>
    .cake-point-order-summary {
      padding: 0.75rem 1rem;
      background: #f4f5f7;
      border-radius: 4px;
    }

    .tz-phone-prefix {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      background: #f8f9fa;
      border-color: #ced4da;
    }

    .tz-phone-group .form-control {
      border-left: 0;
    }

    .cake-point-row--new {
      background: #fffbf0;
    }

    .cake-point-table__actions-col {
      min-width: 160px;
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

    @media (max-width: 991.98px) {
      .sales-filter-row > [class*="col-"]:not(:last-child) {
        margin-bottom: 0.5rem;
      }
    }
  </style>
@endpush

@push('scripts')
  @if(auth()->user()->isOwner() && $items->isNotEmpty() && $staffMembers->isNotEmpty())
    <script src="{{ asset('panel-assets/js/phone-tz.js') }}"></script>
    <script>
      (function () {
        var customerSelect = document.getElementById('cakePointCustomerSelect');
        var customerName = document.getElementById('cakePointCustomerName');
        var customerPhoneLocal = document.getElementById('cakePointCustomerPhoneLocal');
        var customerPhoneFull = document.getElementById('cakePointCustomerPhoneFull');
        var itemSelect = document.getElementById('cakePointItemId');
        var quantityInput = document.getElementById('cakePointQuantity');
        var discountInput = document.getElementById('cakePointDiscount');
        var totalEl = document.getElementById('cakePointOrderTotal');
        var subtotalEl = document.getElementById('cakePointSubtotalLine');
        var discountLineEl = document.getElementById('cakePointDiscountLine');
        var form = document.getElementById('cakePointOrderForm');

        function formatMoney(amount) {
          return new Intl.NumberFormat('en-TZ').format(Math.max(0, Math.round(amount))) + ' TZS';
        }

        function fillCustomerFromSelect() {
          if (!customerSelect) return;
          var option = customerSelect.options[customerSelect.selectedIndex];
          if (!option || !option.value) return;
          customerName.value = option.dataset.name || '';
          var phone = option.dataset.phone || '';
          if (phone.startsWith('+255')) {
            customerPhoneLocal.value = phone.replace('+255', '').replace(/^0/, '');
          } else if (phone.startsWith('255')) {
            customerPhoneLocal.value = phone.slice(3).replace(/^0/, '');
          } else {
            customerPhoneLocal.value = phone.replace(/^0/, '');
          }
          customerPhoneLocal.dispatchEvent(new Event('input'));
        }

        function updateTotal() {
          var option = itemSelect.options[itemSelect.selectedIndex];
          var price = option && option.dataset.price ? parseFloat(option.dataset.price) : 0;
          var qty = parseFloat(quantityInput.value) || 0;
          var discount = parseFloat(discountInput.value) || 0;
          var subtotal = price * qty;
          var maxDiscount = subtotal > 0 ? subtotal : 0;

          if (discount > maxDiscount) {
            discountInput.setCustomValidity('Discount cannot exceed the line total.');
          } else {
            discountInput.setCustomValidity('');
          }

          if (!price || !qty) {
            subtotalEl.classList.add('d-none');
            discountLineEl.classList.add('d-none');
            totalEl.textContent = '—';
            return;
          }

          var total = Math.max(0, subtotal - discount);

          subtotalEl.textContent = 'Subtotal: ' + formatMoney(subtotal);
          subtotalEl.classList.remove('d-none');

          if (discount > 0) {
            discountLineEl.textContent = 'Discount: -' + formatMoney(discount);
            discountLineEl.classList.remove('d-none');
          } else {
            discountLineEl.classList.add('d-none');
          }

          totalEl.textContent = formatMoney(total);
        }

        if (customerSelect) {
          customerSelect.addEventListener('change', fillCustomerFromSelect);
        }

        itemSelect.addEventListener('change', updateTotal);
        quantityInput.addEventListener('input', updateTotal);
        discountInput.addEventListener('input', updateTotal);
        updateTotal();

        if (form) {
          form.addEventListener('submit', function () {
            customerPhoneLocal.dispatchEvent(new Event('input'));
          });
        }

        @if(old('customer_phone'))
          var oldPhone = @json(old('customer_phone'));
          if (oldPhone && oldPhone.startsWith('255')) {
            customerPhoneLocal.value = oldPhone.slice(3).replace(/^0/, '');
            customerPhoneLocal.dispatchEvent(new Event('input'));
          }
        @endif

        @if(old('customer_id'))
          fillCustomerFromSelect();
        @endif
      })();
    </script>
  @endif

  @if(auth()->user()->isOwner() && $staffMembers->isNotEmpty())
    <script>
      document.querySelectorAll('.js-assign-cake-point').forEach(function (button) {
        button.addEventListener('click', function () {
          document.getElementById('assignCakePointForm').action = button.dataset.assignUrl;
          document.getElementById('assignSaleNumber').textContent = button.dataset.saleNumber;
          document.getElementById('assignStaffId').value = button.dataset.currentStaff || '';
        });
      });
    </script>
  @endif

  @if(auth()->user()->canPaySales())
    <script>
      window.salesPaymentProviders = @json($paymentProviderOptions);
    </script>
    <script src="{{ asset('panel-assets/js/plugins/select2.min.js') }}"></script>
    <script src="{{ asset('panel-assets/js/sales-index.js') }}"></script>
  @endif

  <script>
    (function () {
      var form = document.getElementById('cakePointFilterForm');
      if (!form) {
        return;
      }

      var searchInput = document.getElementById('cakePointSearchInput');
      var timer = null;

      if (searchInput) {
        searchInput.addEventListener('input', function () {
          clearTimeout(timer);
          timer = setTimeout(function () {
            form.submit();
          }, 400);
        });
      }

      form.querySelectorAll('.js-cake-point-filter').forEach(function (field) {
        field.addEventListener('change', function () {
          form.submit();
        });
      });

      form.addEventListener('submit', function () {
        clearTimeout(timer);
      });
    })();
  </script>
@endpush

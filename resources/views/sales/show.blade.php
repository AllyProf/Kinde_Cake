@extends('layouts.app')

@section('title', 'Sale '.$sale->sale_number)

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-file-text-o"></i> {{ $sale->sale_number }}</h1>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
      <li class="breadcrumb-item">{{ $sale->sale_number }}</li>
    </ul>
  </div>

  <div class="row mb-3">
    <div class="col-md-12 sale-show-actions">
      @if(auth()->user()->canPaySales() && $sale->canBePaid())
          <button type="button"
            class="btn btn-success js-pay-sale"
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
      @if(auth()->user()->hasPermission('orders.manage'))
        @if($sale->canBeEdited())
          <a class="btn btn-primary" href="{{ route('sales.edit', $sale) }}"><i class="fa fa-edit"></i> Edit</a>
        @endif
        @if($sale->isPending() && (float) $sale->amount_paid <= 0)
          <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline js-swal-delete">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"
              data-title="Delete sale?"
              data-text="Mark {{ $sale->sale_number }} as deleted? Ingredient stock will be restored. The sale will stay in this list.">
              <i class="fa fa-trash"></i> Delete
            </button>
          </form>
        @endif
      @endif
      @if(auth()->user()->isOwner() && ! $sale->isVoided() && $cakePointStaff->isNotEmpty())
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#assignCakePointModal"
          title="Assign this order to a staff member">
          <i class="fa fa-birthday-cake"></i>
          {{ $sale->isAssigned() ? 'Reassign order' : 'Assign order' }}
        </button>
      @endif
      <a class="btn btn-secondary" href="{{ route('sales.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-5">
      <div class="tile">
        <h3 class="tile-title">Info</h3>
        <div class="tile-body">
          <p class="mb-1"><strong>Date:</strong> {{ $sale->sold_at->format('d M Y H:i') }}</p>
          <p class="mb-1"><strong>Customer:</strong> {{ $sale->customer_name ?: '—' }}</p>
          <p class="mb-1"><strong>Phone:</strong> {{ $sale->customer_phone ?: '—' }}</p>
          <p class="mb-1"><strong>Sold by:</strong> {{ $sale->user?->name ?? '—' }}</p>
          @if($sale->isAssigned())
            <p class="mb-1"><strong>Cake point staff:</strong> {{ $sale->assignedTo?->name ?? '—' }}</p>
            <p class="mb-1"><strong>Assigned at:</strong> {{ $sale->assigned_at?->format('d M Y H:i') ?? '—' }}</p>
            @if($sale->orderSmsWasSent())
              <p class="mb-1"><strong>Staff notified:</strong> {{ $sale->assignment_sms_sent_at->format('d M Y H:i') }}</p>
            @endif
          @endif
          <p class="mb-1">
            <strong>Status:</strong>
            <span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span>
          </p>
          <p class="mb-1"><strong>Payment:</strong> {{ $sale->paymentMethodLabel() }}</p>
          @if($sale->payment_reference)
            <p class="mb-1"><strong>Reference:</strong> {{ $sale->payment_reference }}</p>
          @endif
          @if($sale->hasOutstandingBalance() && $sale->credit_repayment_date)
            <p class="mb-1"><strong>Balance due by:</strong> {{ $sale->credit_repayment_date->format('d M Y') }}</p>
          @elseif($sale->isCreditPayment() && $sale->credit_repayment_date)
            <p class="mb-1"><strong>Repayment due:</strong> {{ $sale->credit_repayment_date->format('d M Y') }}</p>
          @endif
          @if($sale->paid_at)
            <p class="mb-1"><strong>Paid at:</strong> {{ $sale->paid_at->format('d M Y H:i') }}</p>
          @endif
          @if($sale->cancelled_at)
            <p class="mb-1"><strong>Cancelled at:</strong> {{ $sale->cancelled_at->format('d M Y H:i') }}</p>
          @endif
          @if($sale->deleted_at)
            <p class="mb-1"><strong>Deleted at:</strong> {{ $sale->deleted_at->format('d M Y H:i') }}</p>
          @endif
          @if($sale->wasEdited())
            <p class="mb-1"><strong>Last edited:</strong> {{ $sale->edited_at->format('d M Y H:i') }} <span class="badge badge-info">Edited</span></p>
          @endif
          <p class="mb-1"><strong>Total:</strong> <span class="text-success">{{ $sale->formattedTotal() }}</span></p>
          <p class="mb-1"><strong>Paid:</strong> {{ $sale->formattedAmountPaid() }}</p>
          <p class="mb-1"><strong>Balance due:</strong>
            @if($sale->hasOutstandingBalance())
              <span class="text-danger">{{ $sale->formattedBalanceDue() }}</span>
            @else
              <span class="text-success">0 TZS</span>
            @endif
          </p>
          @if($sale->notes)
            <hr>
            <p class="mb-0"><strong>Notes:</strong><br>{{ $sale->notes }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="tile">
        <h3 class="tile-title">Items sold</h3>
        <div class="tile-body">
          <div class="table-responsive detail-table-wrap">
            <table class="table table-bordered mb-0 detail-card-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Discount</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach($sale->items as $line)
                  <tr>
                    <td data-label="Item">{{ $line->item?->name ?? '—' }}</td>
                    <td data-label="Qty">{{ number_format((float) $line->quantity, 0) }} {{ $line->item?->packageUnit?->symbol }}</td>
                    <td data-label="Price">{{ number_format((float) $line->unit_price, 0) }} TZS</td>
                    <td data-label="Discount">
                      @if((float) $line->discount > 0)
                        -{{ number_format((float) $line->discount, 0) }} TZS
                      @else
                        —
                      @endif
                    </td>
                    <td data-label="Total"><strong>{{ number_format((float) $line->line_total, 0) }} TZS</strong></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      @if($sale->ingredientUsages->isNotEmpty())
        <div class="tile">
          <h3 class="tile-title">Ingredients used</h3>
          <div class="tile-body">
            <div class="table-responsive detail-table-wrap">
              <table class="table table-bordered mb-0 detail-card-table">
                <thead>
                  <tr>
                    <th>Ingredient</th>
                    <th>Used</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($sale->ingredientUsages as $usage)
                    <tr>
                      <td data-label="Ingredient">{{ $usage->ingredient?->name ?? '—' }}</td>
                      <td data-label="Used">
                        {{ number_format((float) $usage->quantity_used, 0) }}
                        {{ $usage->ingredient?->usagePackageUnit?->symbol }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      @if($sale->payments->isNotEmpty())
        <div class="tile">
          <h3 class="tile-title">Payment history</h3>
          <div class="tile-body">
            <div class="table-responsive detail-table-wrap">
              <table class="table table-bordered mb-0 detail-card-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Recorded by</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($sale->payments as $payment)
                    <tr>
                      <td data-label="Date">{{ $payment->paid_at->format('d M Y H:i') }}</td>
                      <td data-label="Amount"><strong>{{ $payment->formattedAmount() }}</strong></td>
                      <td data-label="Method">
                        {{ $payment->paymentMethodLabel() }}
                        @if($payment->customer_name)
                          <br><small class="text-muted">{{ $payment->customer_name }}</small>
                        @endif
                        @if($payment->credit_repayment_date)
                          <br><small class="text-muted">Due {{ $payment->credit_repayment_date->format('d M Y') }}</small>
                        @endif
                      </td>
                      <td data-label="Reference">{{ $payment->payment_reference ?: '—' }}</td>
                      <td data-label="Recorded by">{{ $payment->user?->name ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>

  @if(auth()->user()->canPaySales() && $sale->canBePaid())
    @include('sales.partials._pay-modal')
  @endif

  @if(auth()->user()->isOwner() && ! $sale->isVoided() && $cakePointStaff->isNotEmpty())
    <div class="modal fade" id="assignCakePointModal" tabindex="-1" role="dialog" aria-labelledby="assignCakePointModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ route('sales.assign', $sale) }}" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="assignCakePointModalLabel">Assign order</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p class="text-muted mb-3">
                Assign order <strong>{{ $sale->sale_number }}</strong> to a staff member.
                @if($sale->isAssigned())
                  Currently assigned to <strong>{{ $sale->assignedTo?->name }}</strong>.
                @endif
              </p>
              <div class="form-group">
                <label class="control-label" for="cakePointStaffId">Staff member</label>
                <select class="form-control" id="cakePointStaffId" name="staff_id" required>
                  <option value="">Select staff...</option>
                  @foreach($cakePointStaff as $staff)
                    <option value="{{ $staff->id }}" @selected($sale->assigned_to_user_id === $staff->id)>
                      {{ $staff->name }}{{ $staff->phone ? ' · '.$staff->phone : ' · no phone' }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group mb-0">
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="send_sms" value="1" @checked($smsReady) @disabled(! $smsReady)>
                    Notify staff by SMS
                  </label>
                </div>
                @unless($smsReady)
                  <small class="text-muted">Configure SMS in Settings to enable phone notifications.</small>
                @endunless
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
@endsection

@push('scripts')
  @if(auth()->user()->canPaySales() && $sale->canBePaid())
    <script>
      window.salesPaymentProviders = @json($paymentProviderOptions);
    </script>
    <script src="{{ asset('panel-assets/js/plugins/select2.min.js') }}"></script>
    <script src="{{ asset('panel-assets/js/sales-index.js') }}"></script>
  @endif
@endpush

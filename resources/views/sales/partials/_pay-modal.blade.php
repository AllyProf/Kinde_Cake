<div class="modal fade" id="paySaleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" id="paySaleForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Record payment</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p class="mb-1"><strong id="paySaleNumber">—</strong></p>
          <div class="pay-summary-box mb-3">
            <div class="d-flex justify-content-between"><span>Total</span><strong id="paySaleTotal">—</strong></div>
            <div class="d-flex justify-content-between"><span>Paid so far</span><strong id="paySalePaid">0 TZS</strong></div>
            <div class="d-flex justify-content-between text-danger"><span>Balance due</span><strong id="paySaleBalance">—</strong></div>
          </div>

          <div class="form-group">
            <label class="control-label d-flex justify-content-between align-items-center">
              <span>Amount to pay <span class="text-danger">*</span></span>
              <button type="button" class="btn btn-link btn-sm p-0" id="payFullBalanceBtn">Pay full balance</button>
            </label>
            <input type="number" class="form-control" name="amount" id="payAmountInput" min="1" step="1" required>
            <small class="text-muted">Enter a partial amount or pay the full remaining balance.</small>
          </div>

          <div class="form-group">
            <label class="control-label">Payment method</label>
            <select class="form-control" name="payment_method" id="payMethodSelect" required>
              <option value="">Select method</option>
              @foreach(\App\Models\Sale::paymentMethodOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group d-none" id="payProviderGroup">
            <label class="control-label" id="payProviderLabel">Provider</label>
            <select class="form-control" name="payment_provider_id" id="payProviderSelect">
              <option value="">Select provider</option>
            </select>
            <small class="text-muted">
              Configure providers in <a href="{{ route('settings.index', ['tab' => 'payments']) }}">Settings → Payments</a>.
            </small>
          </div>

          <div class="form-group d-none" id="payReferenceGroup">
            <label class="control-label">Reference number <span class="text-muted">(optional)</span></label>
            <input type="text" class="form-control" name="payment_reference" id="payReferenceInput"
              maxlength="120" placeholder="Transaction / receipt reference">
          </div>

          <div class="d-none" id="payBalanceInfoGroup">
            <p class="text-muted small mb-2" id="payBalanceInfoHint">
              Required when this payment leaves a balance, or when recording credit (debt).
            </p>
            <div class="form-group">
              <label class="control-label">Customer</label>
              <select class="form-control js-pay-customer-select" id="payCreditCustomerSelect">
                <option value="">Search customer</option>
                @foreach($payCustomers as $customer)
                  <option value="{{ $customer->id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}">
                    {{ $customer->displayLabel() }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="control-label">Customer name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="customer_name" id="payCreditCustomerName" maxlength="255">
              <input type="hidden" name="customer_id" id="payCreditCustomerId" value="">
            </div>
            <div class="form-group">
              <label class="control-label">Customer phone</label>
              <input type="text" class="form-control" name="customer_phone" id="payCreditCustomerPhone" maxlength="30" placeholder="+255...">
            </div>
            <div class="form-group mb-0">
              <label class="control-label">Repayment date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="credit_repayment_date" id="payCreditRepaymentDate"
                min="{{ now()->format('Y-m-d') }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success" id="paySubmitBtn"><i class="fa fa-check"></i> Confirm payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

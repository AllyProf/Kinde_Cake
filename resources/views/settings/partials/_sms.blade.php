<div class="row">
  <div class="col-lg-7">
    <div class="tile">
      <h3 class="tile-title">SMS Notifications</h3>
      <div class="tile-body">
        <form action="{{ route('settings.sms.update') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="form-group">
            <div class="form-check">
              <label class="form-check-label">
                <input type="checkbox" class="form-check-input" name="sms_enabled" value="1"
                  @checked(old('sms_enabled', $sms['enabled']))>
                Enable SMS notifications
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="control-label">Provider</label>
            <select class="form-control @error('sms_driver') is-invalid @enderror" name="sms_driver">
              <option value="beem" @selected(old('sms_driver', $sms['driver']) === 'beem')>Beem Africa (live)</option>
              <option value="log" @selected(old('sms_driver', $sms['driver']) === 'log')>Log only (testing)</option>
            </select>
            @error('sms_driver')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Use <strong>Log only</strong> on local machines without SMS credits.</small>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">API key</label>
                <input type="text" class="form-control @error('sms_api_key') is-invalid @enderror" name="sms_api_key"
                  value="{{ old('sms_api_key', $sms['api_key']) }}" autocomplete="off">
                @error('sms_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Secret key</label>
                <input type="password" class="form-control @error('sms_secret_key') is-invalid @enderror" name="sms_secret_key"
                  value="{{ old('sms_secret_key', $sms['secret_key']) }}" autocomplete="new-password">
                @error('sms_secret_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="control-label">Sender ID</label>
            <input type="text" class="form-control @error('sms_sender_id') is-invalid @enderror" name="sms_sender_id"
              value="{{ old('sms_sender_id', $sms['sender_id']) }}" maxlength="20" placeholder="KINDECAKE">
            @error('sms_sender_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Registered sender name from your Beem Africa account.</small>
          </div>

          <div class="form-group">
            <label class="control-label">Cake point order message</label>
            <textarea class="form-control @error('sms_cake_point_template') is-invalid @enderror"
              name="sms_cake_point_template" rows="4" maxlength="480" required>{{ old('sms_cake_point_template', $sms['order_received_template']) }}</textarea>
            @error('sms_cake_point_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">
              Sent to staff when the owner assigns an order. Placeholders:
              <code>{staff_name}</code>,
              <code>{sale_number}</code>,
              <code>{customer_name}</code>,
              <code>{customer_phone}</code>,
              <code>{items_summary}</code>,
              <code>{total}</code>,
              <code>{business_name}</code>
            </small>
          </div>

          <div class="tile-footer px-0 pb-0">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-check-circle"></i> Save SMS Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="tile">
      <h3 class="tile-title">How it works</h3>
      <div class="tile-body">
        <ol class="mb-3 pl-3">
          <li class="mb-2">Configure your Beem Africa API credentials above.</li>
          <li class="mb-2">Make sure staff members have phone numbers on their profiles.</li>
          <li class="mb-2">From a sale or the Orders page, the owner selects a staff member to handle the order.</li>
          <li class="mb-2">The staff member receives an SMS with order details (optional) and sees the order on their Orders list.</li>
        </ol>
        <p class="mb-0 text-muted small">
          Only the owner can assign orders to staff. Staff phone should be in Tanzania format, e.g. <code>+255712345678</code>.
        </p>
      </div>
    </div>
  </div>
</div>

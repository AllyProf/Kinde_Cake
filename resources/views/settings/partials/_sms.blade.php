<div class="row">
  <div class="col-lg-7">
    <div class="tile">
      <h3 class="tile-title">SMS Notifications</h3>
      <div class="tile-body">
        <form action="{{ route('settings.sms.update') }}" method="POST" id="smsSettingsForm">
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
            <select class="form-control @error('sms_driver') is-invalid @enderror" name="sms_driver" id="smsDriverSelect">
              <option value="mauzo_link" @selected(old('sms_driver', $sms['driver']) === 'mauzo_link')>Messaging Service (messaging-service.co.tz)</option>
              <option value="beem" @selected(old('sms_driver', $sms['driver']) === 'beem')>Beem Africa (legacy)</option>
              <option value="log" @selected(old('sms_driver', $sms['driver']) === 'log')>Log only (testing)</option>
            </select>
            @error('sms_driver')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Use <strong>Log only</strong> on local machines without SMS credits.</small>
          </div>

          <div class="form-group js-sms-mauzo-fields">
            <label class="control-label">Access token</label>
            <input type="password" class="form-control @error('sms_access_token') is-invalid @enderror" name="sms_access_token"
              value="{{ old('sms_access_token', $sms['access_token']) }}" autocomplete="new-password"
              placeholder="Bearer token from dashboard → Customization → API Keys">
            @error('sms_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">
              Used as <code>Authorization: Bearer …</code> on the v2 API (or as <code>token</code> if link transport is enabled in <code>.env</code>).
              @if($sms['token_from_env'] ?? false)
                <span class="d-block mt-1 text-success">A token is loaded from <code>.env</code> (<code>SMS_MAUZO_LINK_TOKEN</code>). Leave this blank to keep using it.</span>
              @endif
            </small>
          </div>

          <div class="row js-sms-beem-fields">
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
              value="{{ old('sms_sender_id', $sms['sender_id']) }}" maxlength="20" placeholder="Kinde Cake">
            @error('sms_sender_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted js-sms-sender-hint">Registered sender ID (e.g. <strong>Kinde Cake</strong>).</small>
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
          <li class="mb-2">Paste your API access token from the messaging dashboard (Customization → API Keys), or set <code>SMS_MAUZO_LINK_TOKEN</code> in <code>.env</code>.</li>
          <li class="mb-2">Make sure staff members have phone numbers on their profiles.</li>
          <li class="mb-2">From a sale or the Orders page, the owner selects a staff member to handle the order.</li>
          <li class="mb-2">The staff member receives an SMS with order details (optional) and sees the order on their Orders list.</li>
        </ol>
        <p class="mb-0 text-muted small">
          Live sends use <code>POST /api/sms/v2/text/single</code> with JSON body (<code>from</code>, <code>to</code>, <code>text</code>). Numbers are normalized to E.164 Tanzania format, e.g. <code>0616775800</code> → <code>255616775800</code>.
        </p>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  var $driver = $('#smsDriverSelect');
  if (! $driver.length) return;

  function syncSmsDriverLabels() {
    var driver = $driver.val();
    var isBeem = driver === 'beem';
    var isMauzo = driver === 'mauzo_link';

    $('.js-sms-mauzo-fields').toggle(isMauzo);
    $('.js-sms-beem-fields').toggle(isBeem);
    $('.js-sms-sender-hint').text(
      isBeem
        ? 'Registered sender name from your Beem Africa account.'
        : 'Registered sender ID on messaging-service.co.tz (e.g. Kinde Cake).'
    );
  }

  $driver.on('change', syncSmsDriverLabels);
  syncSmsDriverLabels();
})();
</script>
@endpush

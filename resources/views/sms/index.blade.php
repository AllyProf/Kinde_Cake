@extends('layouts.app')

@section('title', 'SMS')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-commenting"></i> SMS</h1>
      <p>Send messages to customers or staff, or schedule them for later.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">SMS</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Message history</h3>
          <p>
            @if(auth()->user()->canSendSms())
              <button type="button" class="btn btn-primary icon-btn" data-toggle="modal" data-target="#composeSmsModal"
                @disabled(! $smsReady)>
                <i class="fa fa-paper-plane"></i> Compose SMS
              </button>
            @endif
          </p>
        </div>
        <div class="tile-body">
          @if(! $smsReady)
            <div class="alert alert-warning">
              <i class="fa fa-exclamation-triangle"></i>
              SMS is not enabled or configured.
              @if(auth()->user()->isOwner())
                <a href="{{ route('settings.index', ['tab' => 'sms']) }}">Set up SMS in Settings</a>.
              @else
                Ask the owner to configure SMS in Settings.
              @endif
            </div>
          @endif

          <form method="GET" action="{{ route('sms.index') }}" class="form-inline mb-3">
            <label class="control-label mr-2 mb-2" for="smsStatusFilter">Status</label>
            <select class="form-control mr-2 mb-2" id="smsStatusFilter" name="status" onchange="this.form.submit()">
              <option value="">All</option>
              <option value="pending" @selected($statusFilter === 'pending')>Pending / Scheduled</option>
              <option value="sent" @selected($statusFilter === 'sent')>Sent</option>
              <option value="failed" @selected($statusFilter === 'failed')>Failed</option>
              <option value="cancelled" @selected($statusFilter === 'cancelled')>Cancelled</option>
            </select>
            @if($statusFilter)
              <a href="{{ route('sms.index') }}" class="btn btn-secondary mb-2">Clear filter</a>
            @endif
          </form>

          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Recipient</th>
                  <th>Message</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Scheduled</th>
                  <th>Sent</th>
                  <th>By</th>
                  @if(auth()->user()->canSendSms())
                    <th>Actions</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($messages as $message)
                  <tr>
                    <td>{{ $messages->firstItem() + $loop->index }}</td>
                    <td>
                      <strong>{{ $message->recipientLabel() }}</strong>
                      @if($message->last_error)
                        <br><small class="text-danger">{{ str($message->last_error)->limit(80) }}</small>
                      @endif
                    </td>
                    <td>
                      <span title="{{ $message->message }}">{{ $message->messagePreview(80) }}</span>
                    </td>
                    <td>
                      @if($message->type === \App\Models\SmsMessage::TYPE_CAKE_POINT)
                        <span class="badge badge-info">Orders</span>
                      @else
                        <span class="badge badge-light">Manual</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $message->statusBadgeClass() }}">{{ $message->statusLabel() }}</span>
                    </td>
                    <td>{{ $message->scheduled_at?->format('d M Y H:i') ?: '—' }}</td>
                    <td>{{ $message->sent_at?->format('d M Y H:i') ?: '—' }}</td>
                    <td>{{ $message->createdBy?->name ?: '—' }}</td>
                    @if(auth()->user()->canSendSms())
                      <td>
                        @if($message->canBeCancelled() && $message->isScheduled())
                          <form action="{{ route('sms.destroy', $message) }}" method="POST" class="d-inline js-swal-delete">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                              data-title="Cancel scheduled SMS?"
                              data-text="This message will not be sent."
                              data-confirm="Yes, cancel"
                              data-cancel="Keep">
                              <i class="fa fa-times"></i> Cancel
                            </button>
                          </form>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ auth()->user()->canSendSms() ? 9 : 8 }}" class="text-center text-muted py-4">
                      No SMS messages yet.
                      @if(auth()->user()->canSendSms() && $smsReady)
                        <button type="button" class="btn btn-link p-0 align-baseline" data-toggle="modal" data-target="#composeSmsModal">
                          Send your first message
                        </button>.
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          {{ $messages->links() }}
        </div>
      </div>
    </div>
  </div>

  @if(auth()->user()->canSendSms())
    <div class="modal fade" id="composeSmsModal" tabindex="-1" role="dialog" aria-labelledby="composeSmsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <form action="{{ route('sms.store') }}" method="POST" id="composeSmsForm">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="composeSmsModalLabel"><i class="fa fa-paper-plane"></i> Compose SMS</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="control-label">Send to</label>
                <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
                  <label class="btn btn-outline-primary flex-fill mb-2 mr-1 {{ old('recipient_type', 'manual') === 'manual' ? 'active' : '' }}">
                    <input type="radio" name="recipient_type" value="manual" autocomplete="off"
                      @checked(old('recipient_type', 'manual') === 'manual')> Phone number
                  </label>
                  <label class="btn btn-outline-primary flex-fill mb-2 mr-1 {{ old('recipient_type') === 'customer' ? 'active' : '' }}">
                    <input type="radio" name="recipient_type" value="customer" autocomplete="off"
                      @checked(old('recipient_type') === 'customer')> Customer
                  </label>
                  <label class="btn btn-outline-primary flex-fill mb-2 {{ old('recipient_type') === 'staff' ? 'active' : '' }}">
                    <input type="radio" name="recipient_type" value="staff" autocomplete="off"
                      @checked(old('recipient_type') === 'staff')> Staff
                  </label>
                </div>
              </div>

              <div class="js-recipient-panel" data-recipient-type="manual">
                <div class="form-group">
                  <label class="control-label" for="smsPhone">Phone number <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('phone') is-invalid @enderror" id="smsPhone" name="phone"
                    value="{{ old('phone') }}" placeholder="e.g. 0712 345 678">
                  @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="js-recipient-panel d-none" data-recipient-type="customer">
                <div class="form-group">
                  <label class="control-label" for="smsCustomerId">Customer <span class="text-danger">*</span></label>
                  <select class="form-control @error('customer_id') is-invalid @enderror" id="smsCustomerId" name="customer_id">
                    <option value="">Select customer...</option>
                    @foreach($customers as $customer)
                      <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                        {{ $customer->name }} · {{ $customer->phone }}
                      </option>
                    @endforeach
                  </select>
                  @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  @if($customers->isEmpty())
                    <small class="text-muted">No customers with phone numbers yet.</small>
                  @endif
                </div>
              </div>

              <div class="js-recipient-panel d-none" data-recipient-type="staff">
                <div class="form-group">
                  <label class="control-label" for="smsStaffId">Staff member <span class="text-danger">*</span></label>
                  <select class="form-control @error('staff_id') is-invalid @enderror" id="smsStaffId" name="staff_id">
                    <option value="">Select staff...</option>
                    @foreach($staffMembers as $staff)
                      <option value="{{ $staff->id }}" @selected(old('staff_id') == $staff->id)>
                        {{ $staff->name }} · {{ $staff->phone }}
                      </option>
                    @endforeach
                  </select>
                  @error('staff_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-group">
                <label class="control-label" for="smsMessage">Message <span class="text-danger">*</span></label>
                <textarea class="form-control @error('message') is-invalid @enderror" id="smsMessage" name="message"
                  rows="4" maxlength="480" required placeholder="Type your message...">{{ old('message') }}</textarea>
                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted"><span id="smsCharCount">0</span> / 480 characters</small>
              </div>

              <div class="form-group">
                <label class="control-label">When to send</label>
                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                  <label class="btn btn-outline-secondary flex-fill {{ old('send_option', 'now') === 'now' ? 'active' : '' }}">
                    <input type="radio" name="send_option" value="now" autocomplete="off"
                      @checked(old('send_option', 'now') === 'now')> Send now
                  </label>
                  <label class="btn btn-outline-secondary flex-fill {{ old('send_option') === 'schedule' ? 'active' : '' }}">
                    <input type="radio" name="send_option" value="schedule" autocomplete="off"
                      @checked(old('send_option') === 'schedule')> Schedule
                  </label>
                </div>
              </div>

              <div class="form-group js-schedule-panel {{ old('send_option') === 'schedule' ? '' : 'd-none' }}">
                <label class="control-label" for="smsScheduledAt">Schedule for <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror"
                  id="smsScheduledAt" name="scheduled_at" value="{{ old('scheduled_at') }}">
                @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Messages are checked every minute once scheduled.</small>
              </div>

              @error('sms')
                <div class="alert alert-danger mb-0">{{ $message }}</div>
              @enderror
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" @disabled(! $smsReady)>
                <i class="fa fa-paper-plane"></i> Send
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
  <script>
    (function () {
      var $modal = $('#composeSmsModal');
      if (!$modal.length) {
        return;
      }

      var $message = $('#smsMessage');
      var $charCount = $('#smsCharCount');

      function updateCharCount() {
        $charCount.text(($message.val() || '').length);
      }

      function selectedRecipientType() {
        return $('input[name="recipient_type"]:checked').val() || 'manual';
      }

      function toggleRecipientPanels() {
        var type = selectedRecipientType();
        $('.js-recipient-panel').addClass('d-none');
        $('.js-recipient-panel[data-recipient-type="' + type + '"]').removeClass('d-none');
      }

      function toggleSchedulePanel() {
        var schedule = $('input[name="send_option"]:checked').val() === 'schedule';
        $('.js-schedule-panel').toggleClass('d-none', !schedule);
        $('#smsScheduledAt').prop('required', schedule);
      }

      $('input[name="recipient_type"]').on('change', toggleRecipientPanels);
      $('input[name="send_option"]').on('change', toggleSchedulePanel);
      $message.on('input', updateCharCount);

      toggleRecipientPanels();
      toggleSchedulePanel();
      updateCharCount();

      var minSchedule = new Date();
      minSchedule.setMinutes(minSchedule.getMinutes() + 1);
      $('#smsScheduledAt').attr('min', minSchedule.toISOString().slice(0, 16));

      @if($errors->any() && old('_token'))
        $modal.modal('show');
      @endif
    })();
  </script>
@endpush

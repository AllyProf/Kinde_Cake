@extends('layouts.app')

@section('title', 'Close Day')

@section('content')
  @php
    $personalSummary = $personalClose?->summary ?? $summary;
    $businessSummary = $existingClose?->summary ?? $ownerSummary;
    $personalDayLocked = (bool) ($existingClose || $personalClose);
    $canManageExpenses = ! $personalDayLocked && auth()->user()->canClosePersonalDay();
    $canClosePersonal = ! $existingClose && ! $personalClose && auth()->user()->canClosePersonalDay()
      && (! $isOwner || $ownerHasActivity);
    $canCloseBusiness = $isOwner && $ownerCanClose && ! $existingClose;
    $ownerShowBusinessSummary = $isOwner && ($existingClose || $ownerSummary);
  @endphp

  <div class="app-title">
    <div>
      <h1><i class="fa fa-calendar-check-o"></i> Close Day</h1>
      @if($isOwner)
        <p>Close your personal day if you worked today, wait for staff, then close the business day.</p>
      @else
        <p>Review your sales and payments, record expenses, then close your day for the owner.</p>
      @endif
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Close Day</li>
    </ul>
  </div>

  @if($unclosedPreviousDays->isNotEmpty())
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="alert alert-warning day-close-missed-alert mb-0">
          <div class="day-close-missed-alert__head">
            <strong><i class="fa fa-exclamation-triangle"></i> Unclosed previous days</strong>
          </div>
          <p class="mb-2 mt-2">
            @if($isOwner)
              The following past days had business activity but the business day was not closed:
            @else
              You had activity on these past days but have not closed your day yet:
            @endif
          </p>
          <ul class="day-close-missed-list mb-0">
            @foreach($unclosedPreviousDays as $missedDay)
              <li>
                <a href="{{ route('day-closes.index', ['date' => $missedDay['date']]) }}">
                  {{ $missedDay['label'] }}
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  <div class="row mb-3">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <form method="GET" action="{{ route('day-closes.index') }}" class="form-inline day-close-filter-form">
            <label class="control-label mr-2 mb-2" for="dayCloseDate">Business date</label>
            <input type="date" class="form-control mr-2 mb-2" id="dayCloseDate" name="date"
              value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
            <button type="submit" class="btn btn-primary mb-2">
              <i class="fa fa-search"></i> View
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-7">
      @if($isOwner && ! $existingClose && $ownerHasActivity)
        <div class="tile">
          <h3 class="tile-title">My personal day — {{ $selectedDate->format('d M Y') }}</h3>
          <div class="tile-body">
            @if($personalClose)
              <div class="alert alert-success mb-3">
                <i class="fa fa-check-circle"></i>
                You closed your personal day on {{ $personalClose->closed_at->format('d M Y H:i') }}.
              </div>
            @elseif($canClosePersonal)
              <div class="alert alert-info mb-3">
                Record your expenses, then close your personal day before closing the business day.
              </div>
            @endif

            @include('day-closes.partials._summary-table', ['displaySummary' => $personalSummary])

            @if($canManageExpenses)
              <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3 day-close-section-head">
                  <h4 class="mb-0">My expenses</h4>
                  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addExpenseModal">
                    <i class="fa fa-plus"></i> Add expense
                  </button>
                </div>
                @if($expenses->isNotEmpty())
                  @include('day-closes.partials._expenses-table', ['expenses' => $expenses, 'editable' => true])
                @else
                  <p class="text-muted mb-0">No expenses recorded yet.</p>
                @endif
              </div>
            @elseif($personalClose && $expenses->isNotEmpty())
              <div class="mt-4">
                <h4 class="mb-3">My expenses</h4>
                @include('day-closes.partials._expenses-table', ['expenses' => $expenses, 'editable' => false])
              </div>
            @endif

            @if($personalClose?->notes)
              <div class="mt-3">
                <strong>Your notes:</strong>
                <p class="mb-0 text-muted">{{ $personalClose->notes }}</p>
              </div>
            @endif

            @if($canClosePersonal)
              <form action="{{ route('day-closes.staff.store') }}" method="POST" class="mt-4 day-close-submit-form" id="closePersonalDayForm">
                @csrf
                <input type="hidden" name="business_date" value="{{ $selectedDate->format('Y-m-d') }}">
                <div class="form-group">
                  <label class="control-label" for="personalCloseNotes">Notes (optional)</label>
                  <textarea class="form-control" id="personalCloseNotes" name="notes" rows="2"
                    placeholder="Any notes about your day...">{{ old('notes') }}</textarea>
                </div>
                <button type="button" class="btn btn-primary" id="closePersonalDayBtn"
                  data-title="Close your personal day for {{ $selectedDate->format('d M Y') }}?"
                  data-text="This saves your personal totals for today. You cannot change them after this."
                  data-confirm="Yes, close my day"
                  data-cancel="Cancel">
                  <i class="fa fa-check"></i> Close my day
                </button>
              </form>
            @elseif($ownerMustClosePersonal)
              <div class="alert alert-warning mt-4 mb-0">
                <i class="fa fa-exclamation-triangle"></i>
                Close your personal day above before you can close the business day.
              </div>
            @endif
          </div>
        </div>
      @endif

      @if(! $isOwner)
        <div class="tile">
          <h3 class="tile-title">My summary for {{ $selectedDate->format('d M Y') }}</h3>
          <div class="tile-body">
            @if($existingClose)
              <div class="alert alert-success">
                Business day was closed by the owner on {{ $existingClose->closed_at->format('d M Y H:i') }}.
              </div>
            @elseif($personalClose)
              <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                You closed your day on {{ $personalClose->closed_at->format('d M Y H:i') }}.
                Waiting for the owner to close the business day.
              </div>
            @elseif($canClosePersonal)
              <div class="alert alert-info mb-3">
                Record any expenses used today (food, transport, etc.), then close your day to send it to the owner.
              </div>
            @endif

            @include('day-closes.partials._summary-table', ['displaySummary' => $personalSummary])

            @if($canManageExpenses)
              <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3 day-close-section-head">
                  <h4 class="mb-0">My expenses</h4>
                  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addExpenseModal">
                    <i class="fa fa-plus"></i> Add expense
                  </button>
                </div>
                @if($expenses->isNotEmpty())
                  @include('day-closes.partials._expenses-table', ['expenses' => $expenses, 'editable' => true])
                @else
                  <p class="text-muted mb-0">No expenses recorded yet.</p>
                @endif
              </div>
            @elseif($personalClose && $expenses->isNotEmpty())
              <div class="mt-4">
                <h4 class="mb-3">My expenses</h4>
                @include('day-closes.partials._expenses-table', ['expenses' => $expenses, 'editable' => false])
              </div>
            @endif

            @if($personalClose?->notes)
              <div class="mt-3">
                <strong>Your notes:</strong>
                <p class="mb-0 text-muted">{{ $personalClose->notes }}</p>
              </div>
            @endif

            @if($canClosePersonal)
              <form action="{{ route('day-closes.staff.store') }}" method="POST" class="mt-4 day-close-submit-form" id="closePersonalDayForm">
                @csrf
                <input type="hidden" name="business_date" value="{{ $selectedDate->format('Y-m-d') }}">
                <div class="form-group">
                  <label class="control-label" for="personalCloseNotes">Closing notes (optional)</label>
                  <textarea class="form-control" id="personalCloseNotes" name="notes" rows="2"
                    placeholder="Any notes for the owner...">{{ old('notes') }}</textarea>
                </div>
                <button type="button" class="btn btn-success" id="closePersonalDayBtn"
                  data-title="Close your day for {{ $selectedDate->format('d M Y') }}?"
                  data-text="This sends your totals to the owner. You cannot change your day after this."
                  data-confirm="Yes, close my day"
                  data-cancel="Cancel">
                  <i class="fa fa-lock"></i> Close my day
                </button>
              </form>
            @endif
          </div>
        </div>
      @endif

      @if($isOwner)
        <div class="tile {{ $ownerHasActivity && ! $existingClose ? 'mt-3' : '' }}">
          <h3 class="tile-title">Business summary for {{ $selectedDate->format('d M Y') }}</h3>
          <div class="tile-body">
            @if($existingClose)
              <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                Business day closed on {{ $existingClose->closed_at->format('d M Y H:i') }}.
              </div>
              @include('day-closes.partials._summary-table', ['displaySummary' => $businessSummary])

              @if(! empty($businessSummary['staff_closes']))
                <div class="mt-4">
                  <h4 class="mb-3">All submissions</h4>
                  @include('day-closes.partials._staff-closes-table', [
                    'staffCloses' => collect($businessSummary['staff_closes']),
                    'fromSnapshot' => true,
                  ])
                </div>
              @endif

              @if($existingClose->notes)
                <div class="mt-3">
                  <strong>Owner notes:</strong>
                  <p class="mb-0 text-muted">{{ $existingClose->notes }}</p>
                </div>
              @endif
            @elseif($canCloseBusiness)
              <div class="alert alert-info mb-3">
                All staff have submitted. Review the combined totals below, then close the business day.
              </div>
              @include('day-closes.partials._summary-table', ['displaySummary' => $businessSummary])

              @if($allSubmissions->isNotEmpty())
                <div class="mt-4">
                  <h4 class="mb-3">All submissions</h4>
                  @include('day-closes.partials._staff-closes-table', [
                    'staffCloses' => $allSubmissions,
                    'fromSnapshot' => false,
                  ])
                  <p class="text-muted mb-0 mt-2">
                    <small>Net in hand above is the total of all submissions combined.</small>
                  </p>
                </div>
              @endif

              <form action="{{ route('day-closes.store') }}" method="POST" class="mt-4 day-close-submit-form" id="closeBusinessDayForm">
                @csrf
                <input type="hidden" name="business_date" value="{{ $selectedDate->format('Y-m-d') }}">
                <div class="form-group">
                  <label class="control-label" for="businessCloseNotes">Business closing notes (optional)</label>
                  <textarea class="form-control" id="businessCloseNotes" name="notes" rows="2"
                    placeholder="Any notes for this business day...">{{ old('notes') }}</textarea>
                </div>
                <button type="button" class="btn btn-success" id="closeBusinessDayBtn"
                  data-title="Close business day for {{ $selectedDate->format('d M Y') }}?"
                  data-text="This saves the final business day snapshot. No one can submit for this date after this."
                  data-confirm="Yes, close business day"
                  data-cancel="Cancel">
                  <i class="fa fa-lock"></i> Close business day
                </button>
              </form>
            @elseif($ownerMustClosePersonal)
              <div class="alert alert-warning mb-0">
                <i class="fa fa-clock-o"></i>
                Close your personal day first, then wait for staff submissions before closing the business day.
              </div>
            @elseif($staffWithActivity->isNotEmpty() && $staffCloses->isEmpty())
              <div class="alert alert-warning mb-3">
                <i class="fa fa-clock-o"></i>
                Waiting for staff to close their day. The business summary will appear here once staff submit.
              </div>
              @if($allSubmissions->isNotEmpty())
                <div class="mt-3">
                  <h4 class="mb-3">Submissions so far</h4>
                  @include('day-closes.partials._staff-closes-table', [
                    'staffCloses' => $allSubmissions,
                    'fromSnapshot' => false,
                  ])
                </div>
              @endif
            @elseif($pendingStaff->isNotEmpty())
              <div class="alert alert-warning mb-3">
                <i class="fa fa-clock-o"></i>
                Still waiting for: <strong>{{ $pendingStaff->pluck('name')->join(', ') }}</strong>.
              </div>
              @if($ownerShowBusinessSummary)
                @include('day-closes.partials._summary-table', ['displaySummary' => $businessSummary])
                <div class="mt-4">
                  <h4 class="mb-3">Submissions so far</h4>
                  @include('day-closes.partials._staff-closes-table', [
                    'staffCloses' => $allSubmissions,
                    'fromSnapshot' => false,
                  ])
                </div>
              @endif
            @elseif($staffWithActivity->isEmpty() && ! $ownerHasActivity)
              <div class="alert alert-info mb-3">
                No activity recorded for this day. You can close the business day when ready.
              </div>
              <form action="{{ route('day-closes.store') }}" method="POST" class="mt-2 day-close-submit-form" id="closeBusinessDayForm">
                @csrf
                <input type="hidden" name="business_date" value="{{ $selectedDate->format('Y-m-d') }}">
                <div class="form-group">
                  <label class="control-label" for="businessCloseNotes">Business closing notes (optional)</label>
                  <textarea class="form-control" id="businessCloseNotes" name="notes" rows="2"
                    placeholder="Any notes for this business day...">{{ old('notes') }}</textarea>
                </div>
                <button type="button" class="btn btn-success" id="closeBusinessDayBtn"
                  data-title="Close business day for {{ $selectedDate->format('d M Y') }}?"
                  data-text="This saves the final business day snapshot."
                  data-confirm="Yes, close business day"
                  data-cancel="Cancel">
                  <i class="fa fa-lock"></i> Close business day
                </button>
              </form>
            @elseif($ownerShowBusinessSummary)
              @include('day-closes.partials._summary-table', ['displaySummary' => $businessSummary])
              @if($allSubmissions->isNotEmpty())
                <div class="mt-4">
                  <h4 class="mb-3">All submissions</h4>
                  @include('day-closes.partials._staff-closes-table', [
                    'staffCloses' => $allSubmissions,
                    'fromSnapshot' => false,
                  ])
                </div>
              @endif
            @else
              <p class="text-muted mb-0">Business summary will appear after staff submit their day closes.</p>
            @endif
          </div>
        </div>
      @endif
    </div>

    <div class="col-lg-5">
      @if($isOwner && ! $existingClose && $pendingStaff->isNotEmpty())
        <div class="tile mb-3">
          <h3 class="tile-title">Waiting for staff</h3>
          <div class="tile-body">
            <ul class="mb-0">
              @foreach($pendingStaff as $staff)
                <li>{{ $staff->name }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <div class="tile">
        <h3 class="tile-title">{{ $isOwner ? 'Recent business closes' : 'My recent closes' }}</h3>
        <div class="tile-body">
          <div class="table-responsive detail-table-wrap day-close-history-wrap">
            <table class="table table-hover table-bordered mb-0 detail-card-table day-close-history-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Collected</th>
                  <th>Net</th>
                  @if($isOwner)
                    <th>Closed by</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($history as $close)
                  <tr>
                    <td data-label="Date">
                      <a href="{{ route('day-closes.index', ['date' => $close->business_date->format('Y-m-d')]) }}">
                        {{ $close->formattedBusinessDate() }}
                      </a>
                    </td>
                    <td data-label="Collected">{{ $close->formattedSummaryMoney('total_collected') }}</td>
                    <td data-label="Net">{{ $close->formattedSummaryMoney('net_in_hand') }}</td>
                    @if($isOwner)
                      <td data-label="Closed by">{{ $close->closedBy?->name ?? '—' }}</td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ $isOwner ? 4 : 3 }}" class="text-center text-muted py-3">No closes yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($history->hasPages())
            <div class="mt-3">{{ $history->links() }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if($canManageExpenses)
    <div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ route('day-closes.expenses.store') }}" method="POST">
            @csrf
            <input type="hidden" name="business_date" value="{{ $selectedDate->format('Y-m-d') }}">
            <div class="modal-header">
              <h5 class="modal-title" id="addExpenseModalLabel">Add expense</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="control-label" for="expenseCategory">Category <span class="text-danger">*</span></label>
                <select class="form-control" id="expenseCategory" name="category" required>
                  <option value="">Select category...</option>
                  @foreach(\App\Models\DayExpense::categoryOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label class="control-label" for="expenseAmount">Amount (TZS) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="expenseAmount" name="amount"
                  min="1" step="1" value="{{ old('amount') }}" required placeholder="e.g. 5000">
              </div>
              <div class="form-group mb-0">
                <label class="control-label" for="expenseDescription">Description <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control" id="expenseDescription" name="description"
                  maxlength="255" value="{{ old('description') }}" placeholder="e.g. Lunch, taxi to supplier">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> Save expense
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
    document.addEventListener('DOMContentLoaded', function () {
      $('#closePersonalDayBtn, #closeBusinessDayBtn').on('click', function () {
        var $btn = $(this);
        var $form = $btn.closest('form');

        AppAlerts.confirm({
          title: $btn.data('title') || 'Close this day?',
          text: $btn.data('text') || '',
          type: 'warning',
          confirmText: $btn.data('confirm') || 'Yes, close',
          cancelText: $btn.data('cancel') || 'Cancel',
        }, function () {
          $form.trigger('submit');
        });
      });

      @if($canManageExpenses && (old('category') || old('amount') || old('description')))
        $('#addExpenseModal').modal('show');
      @endif
    });
  </script>
@endpush

@push('styles')
  <style>
    .day-close-provider-row th {
      padding-left: 2rem !important;
      font-size: 0.92rem;
    }

    .day-close-provider-row td {
      font-size: 0.92rem;
    }

    .day-close-expenses-table__actions-col {
      width: 70px;
    }

    .day-close-missed-alert {
      border-left: 4px solid #ffc107;
    }

    .day-close-missed-list {
      padding-left: 1.25rem;
      margin-bottom: 0;
    }

    .day-close-missed-list li + li {
      margin-top: 0.35rem;
    }

    .day-close-missed-list a {
      font-weight: 600;
    }
  </style>
@endpush

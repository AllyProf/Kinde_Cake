@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
  <div class="app-title">
    <div>
      <h1><i class="fa fa-money"></i> Expenses</h1>
      <p>Record daily business expenses (food, transport, supplies, and more)</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Expenses</li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">Expenses for {{ $selectedDate->format('d M Y') }}</h3>
          <p>
            @if($canManage)
              <button type="button" class="btn btn-primary icon-btn" data-toggle="modal" data-target="#addExpenseModal">
                <i class="fa fa-plus"></i> Add expense
              </button>
            @endif
          </p>
        </div>
        <div class="tile-body">
          <form method="GET" action="{{ route('expenses.index') }}" class="mb-3">
            <div class="row align-items-end">
              <div class="col-12 col-md-4 col-lg-3">
                <label class="control-label" for="expenseDateFilter">Date</label>
                <input type="date" class="form-control form-control-sm" id="expenseDateFilter" name="date"
                  value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
              </div>
              <div class="col-12 col-md-4 col-lg-3 mt-2 mt-md-0">
                <label class="control-label" for="expenseCategoryFilter">Category</label>
                <select class="form-control form-control-sm" id="expenseCategoryFilter" name="category">
                  <option value="">All categories</option>
                  @foreach(\App\Models\DayExpense::categoryOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6 col-md-2 col-lg-2 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-sm btn-block">
                  <i class="fa fa-filter"></i> Filter
                </button>
              </div>
            </div>
          </form>

          <p class="mb-3">
            <strong>{{ number_format($totalAmount, 0) }} TZS</strong>
            <span class="text-muted">total · {{ $expenses->count() }} {{ Str::plural('item', $expenses->count()) }}</span>
          </p>

          @if($businessClosed)
            <div class="alert alert-warning">
              This business day is closed. Expenses for this date cannot be changed.
            </div>
          @elseif($personalClosed)
            <div class="alert alert-warning">
              You have already closed your day. Expenses for this date cannot be changed.
            </div>
          @elseif(auth()->user()->hasPermission('expenses.manage') && ! $canManage)
            <div class="alert alert-info">
              Choose an open day above to record a new expense.
            </div>
          @endif

          <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th>Amount</th>
                  <th>Recorded by</th>
                  @if(auth()->user()->hasPermission('expenses.manage'))
                    <th style="min-width:90px">Actions</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($expenses as $expense)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $expense->business_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $expense->categoryLabel() }}</td>
                    <td>{{ $expense->description ?: '—' }}</td>
                    <td><strong>{{ $expense->formattedAmount() }}</strong></td>
                    <td>{{ $expense->recordedBy?->name ?? '—' }}</td>
                    @if(auth()->user()->hasPermission('expenses.manage'))
                      <td>
                        @php
                          $canRemove = $canManage
                            && (auth()->id() === $expense->recorded_by_user_id || auth()->user()->isOwner());
                        @endphp
                        @if($canRemove)
                          <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline js-swal-delete">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                              data-title="Remove this expense?"
                              data-text="{{ $expense->categoryLabel() }} · {{ $expense->formattedAmount() }}">
                              <i class="fa fa-trash"></i>
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
                    <td colspan="{{ auth()->user()->hasPermission('expenses.manage') ? 7 : 6 }}" class="text-center text-muted py-4">
                      No expenses recorded for this date.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($canManage)
    <div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="addExpenseModalLabel">Add expense</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label class="control-label" for="expenseBusinessDate">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('business_date') is-invalid @enderror"
                  id="expenseBusinessDate" name="business_date"
                  value="{{ old('business_date', $selectedDate->format('Y-m-d')) }}"
                  max="{{ now()->format('Y-m-d') }}" required>
                @error('business_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="control-label" for="expenseCategory">Category <span class="text-danger">*</span></label>
                <select class="form-control @error('category') is-invalid @enderror" id="expenseCategory" name="category" required>
                  <option value="">Select category...</option>
                  @foreach(\App\Models\DayExpense::categoryOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group">
                <label class="control-label" for="expenseAmount">Amount (TZS) <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                  id="expenseAmount" name="amount" min="1" step="1"
                  value="{{ old('amount') }}" required placeholder="e.g. 5000">
                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group mb-0">
                <label class="control-label" for="expenseDescription">Description <span class="text-muted">(optional)</span></label>
                <input type="text" class="form-control @error('description') is-invalid @enderror"
                  id="expenseDescription" name="description" maxlength="255"
                  value="{{ old('description') }}" placeholder="e.g. Lunch, taxi to supplier">
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
  @if($canManage && ($errors->any() || old('category') || old('amount') || old('description')))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
          jQuery('#addExpenseModal').modal('show');
        }
      });
    </script>
  @endif
@endpush

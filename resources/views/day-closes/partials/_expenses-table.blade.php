<div class="table-responsive detail-table-wrap day-close-expenses-wrap">
  <table class="table table-hover table-bordered mb-0 detail-card-table day-close-expenses-table">
    <thead>
      <tr>
        @if(! empty($showDate))
          <th>Date</th>
        @endif
        <th>Category</th>
        <th>Description</th>
        <th>Amount</th>
        @if($editable)
          <th class="day-close-expenses-table__actions-col"></th>
        @else
          <th>Recorded by</th>
        @endif
      </tr>
    </thead>
    <tbody>
      @foreach($expenses as $expense)
        @php
          $isModel = is_object($expense);
          $categoryLabel = $isModel ? $expense->categoryLabel() : ($expense['label'] ?? ucfirst($expense['category'] ?? ''));
          $description = $isModel ? $expense->description : ($expense['description'] ?? '—');
          $amount = $isModel ? $expense->amount : ($expense['amount'] ?? 0);
          $recordedBy = $isModel ? ($expense->recordedBy?->name ?? '—') : ($expense['recorded_by'] ?? '—');
          $expenseId = $isModel ? $expense->id : ($expense['id'] ?? null);
          $expenseDate = $isModel
            ? ($expense->business_date?->format('d M Y') ?? '—')
            : (isset($expense['business_date']) ? \Carbon\Carbon::parse($expense['business_date'])->format('d M Y') : '—');
        @endphp
        <tr>
          @if(! empty($showDate))
            <td data-label="Date">{{ $expenseDate }}</td>
          @endif
          <td data-label="Category">{{ $categoryLabel }}</td>
          <td data-label="Description">{{ $description ?: '—' }}</td>
          <td data-label="Amount">{{ number_format($amount, 0) }} TZS</td>
          @if($editable)
            <td class="day-close-expense-actions" data-label="Remove">
              @if($expenseId)
                <form action="{{ route('day-closes.expenses.destroy', $expenseId) }}" method="POST"
                  class="d-inline js-swal-confirm">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"
                    data-title="Remove this expense?"
                    data-text="This cannot be undone.">
                    <i class="fa fa-trash"></i>
                  </button>
                </form>
              @endif
            </td>
          @else
            <td data-label="Recorded by">{{ $recordedBy }}</td>
          @endif
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

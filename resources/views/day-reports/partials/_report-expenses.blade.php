@include('day-reports.partials._report-status')

<h4 class="mb-3">Expenses ({{ number_format($summary['expenses_count'] ?? 0) }})</h4>
@if($report['expenses']->isNotEmpty())
  @include('day-closes.partials._expenses-table', [
    'expenses' => $report['expenses'],
    'editable' => false,
    'showDate' => ! $isDay,
  ])
  <p class="mb-0 mt-3">
    <strong>Total expenses:</strong> {{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS
  </p>
@else
  <p class="text-muted mb-0">No expenses recorded for this period.</p>
@endif

@if(! empty($summary['expenses_by_category']))
  <div class="mt-4">
    <h4 class="mb-3">By category</h4>
    <div class="table-responsive detail-table-wrap">
      <table class="table table-bordered table-sm mb-0 detail-card-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>Entries</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($summary['expenses_by_category'] as $row)
            <tr>
              <td data-label="Category">{{ $row['label'] }}</td>
              <td data-label="Entries">{{ number_format($row['count']) }}</td>
              <td data-label="Amount">{{ number_format($row['amount'], 0) }} TZS</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

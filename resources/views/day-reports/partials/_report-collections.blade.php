@include('day-reports.partials._report-status')

<h4 class="mb-3">Collections & payments</h4>
@include('day-closes.partials._summary-table', ['displaySummary' => $summary])

@if(! empty($summary['expenses_by_category']))
  <div class="mt-4">
    <h4 class="mb-3">Expenses by category (reference)</h4>
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

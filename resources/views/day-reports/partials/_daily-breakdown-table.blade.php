<div class="table-responsive detail-table-wrap">
  <table class="table table-hover table-bordered mb-0 detail-card-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Collected</th>
        <th>Expenses</th>
        <th>Purchases</th>
        <th>Net</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($dailyBreakdown as $day)
        <tr>
          <td data-label="Date">
            <a href="{{ route('day-reports.index', ['period' => 'day', 'date' => $day['date']]) }}">
              {{ $day['label'] }}
            </a>
          </td>
          <td data-label="Collected">{{ number_format($day['collected'], 0) }} TZS</td>
          <td data-label="Expenses">{{ number_format($day['expenses'], 0) }} TZS</td>
          <td data-label="Purchases">{{ number_format($day['receivings'], 0) }} TZS</td>
          <td data-label="Net">{{ number_format($day['net'], 0) }} TZS</td>
          <td data-label="Status">
            @if($day['is_closed'])
              <span class="badge badge-success">Closed</span>
            @else
              <span class="badge badge-secondary">Open</span>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

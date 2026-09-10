@include('day-reports.partials._report-status')

<h4 class="mb-3">Ingredient receivings ({{ number_format($report['receivings_count'] ?? 0) }})</h4>
@if($report['receivings']->isNotEmpty())
  @include('day-reports.partials._receivings-table', ['receivings' => $report['receivings']])
  <p class="mb-0 mt-3">
    <strong>Total purchase cost:</strong> {{ number_format($report['receivings_total'], 0) }} TZS
  </p>
@else
  <p class="text-muted mb-0">No ingredient receivings recorded for this period.</p>
@endif

<div class="mt-4 p-3 bg-light rounded day-report-balance-box">
  <h5 class="mb-3">Impact on cash</h5>
  <div class="day-close-summary-wrap">
    <table class="table table-sm table-borderless mb-0 day-close-summary-table">
      <tbody>
        <tr>
          <th style="width:55%">Net in hand (period)</th>
          <td>{{ number_format($report['net_in_hand'], 0) }} TZS</td>
        </tr>
        <tr>
          <th>Ingredient purchases</th>
          <td class="text-danger">− {{ number_format($report['receivings_total'], 0) }} TZS</td>
        </tr>
        <tr class="day-close-summary-row--total day-close-summary-row--net">
          <th>Balance after purchases</th>
          <td><strong>{{ number_format($report['balance_after_purchases'], 0) }} TZS</strong></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

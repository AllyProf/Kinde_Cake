<div class="mt-4 p-3 bg-light rounded day-report-balance-box">
  <h5 class="mb-3">{{ $isDay ? 'Day' : 'Period' }} balance</h5>
  <div class="day-close-summary-wrap">
    <table class="table table-sm table-borderless mb-0 day-close-summary-table">
      <tbody>
        <tr>
          <th style="width:55%">Total money in</th>
          <td>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</td>
        </tr>
        <tr>
          <th>Expenses used</th>
          <td class="text-danger">− {{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS</td>
        </tr>
        <tr class="day-close-summary-row--total">
          <th>Net in hand</th>
          <td><strong>{{ number_format($report['net_in_hand'], 0) }} TZS</strong></td>
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

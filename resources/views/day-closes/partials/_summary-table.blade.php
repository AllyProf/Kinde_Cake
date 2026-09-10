<div class="day-close-summary-wrap">
<table class="table table-bordered mb-0 day-close-summary-table">
  <tbody>
    <tr>
      <th style="width:45%">Sales recorded</th>
      <td>{{ number_format($displaySummary['sales_count'] ?? 0) }} ({{ number_format($displaySummary['sales_total'] ?? 0, 0) }} TZS)</td>
    </tr>
    <tr>
      <th>Paid sales</th>
      <td>{{ number_format($displaySummary['paid_sales_count'] ?? 0) }}</td>
    </tr>
    <tr>
      <th>Pending / partial sales</th>
      <td>{{ number_format($displaySummary['pending_sales_count'] ?? 0) }} ({{ number_format($displaySummary['pending_sales_total'] ?? 0, 0) }} TZS due)</td>
    </tr>
    <tr>
      <th>Payments recorded</th>
      <td>{{ number_format($displaySummary['payments_count'] ?? 0) }}</td>
    </tr>
    <tr>
      <th>Cash collected</th>
      <td>{{ number_format($displaySummary['cash_collected'] ?? 0, 0) }} TZS</td>
    </tr>
    <tr>
      <th>Mobile collected</th>
      <td>{{ number_format($displaySummary['mobile_collected'] ?? 0, 0) }} TZS</td>
    </tr>
    @foreach($displaySummary['mobile_providers'] ?? [] as $provider)
      <tr class="day-close-provider-row">
        <th class="pl-4 text-muted font-weight-normal">
          <i class="fa fa-mobile"></i> {{ $provider['name'] }}
        </th>
        <td>
          {{ number_format($provider['amount'] ?? 0, 0) }} TZS
          <small class="text-muted">({{ number_format($provider['count'] ?? 0) }} {{ Str::plural('payment', $provider['count'] ?? 0) }})</small>
        </td>
      </tr>
    @endforeach
    <tr>
      <th>Bank collected</th>
      <td>{{ number_format($displaySummary['bank_collected'] ?? 0, 0) }} TZS</td>
    </tr>
    @foreach($displaySummary['bank_providers'] ?? [] as $provider)
      <tr class="day-close-provider-row">
        <th class="pl-4 text-muted font-weight-normal">
          <i class="fa fa-university"></i> {{ $provider['name'] }}
        </th>
        <td>
          {{ number_format($provider['amount'] ?? 0, 0) }} TZS
          <small class="text-muted">({{ number_format($provider['count'] ?? 0) }} {{ Str::plural('payment', $provider['count'] ?? 0) }})</small>
        </td>
      </tr>
    @endforeach
    <tr>
      <th>Credit recorded</th>
      <td>{{ number_format($displaySummary['credit_recorded'] ?? 0, 0) }} TZS</td>
    </tr>
    <tr class="day-close-summary-row--total">
      <th><strong>Total money in</strong></th>
      <td><strong>{{ number_format($displaySummary['total_collected'] ?? 0, 0) }} TZS</strong></td>
    </tr>
    <tr>
      <th>Expenses ({{ number_format($displaySummary['expenses_count'] ?? 0) }})</th>
      <td>{{ number_format($displaySummary['expenses_total'] ?? 0, 0) }} TZS</td>
    </tr>
    @foreach($displaySummary['expenses_by_category'] ?? [] as $expenseCategory)
      <tr class="day-close-provider-row">
        <th class="pl-4 text-muted font-weight-normal">
          <i class="fa fa-tag"></i> {{ $expenseCategory['label'] }}
        </th>
        <td>
          {{ number_format($expenseCategory['amount'] ?? 0, 0) }} TZS
          <small class="text-muted">({{ number_format($expenseCategory['count'] ?? 0) }} {{ Str::plural('item', $expenseCategory['count'] ?? 0) }})</small>
        </td>
      </tr>
    @endforeach
    <tr class="day-close-summary-row--total day-close-summary-row--net">
      <th><strong>Net in hand</strong></th>
      <td><strong>{{ number_format($displaySummary['net_in_hand'] ?? (($displaySummary['total_collected'] ?? 0) - ($displaySummary['expenses_total'] ?? 0)), 0) }} TZS</strong></td>
    </tr>
  </tbody>
</table>
</div>

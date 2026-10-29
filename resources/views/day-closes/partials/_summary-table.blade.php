@php
  $salesCount = (int) ($displaySummary['sales_count'] ?? 0);
  $paidSalesCount = (int) ($displaySummary['paid_sales_count'] ?? 0);
  $pendingSalesCount = (int) ($displaySummary['pending_sales_count'] ?? 0);
  $paymentsCount = (int) ($displaySummary['payments_count'] ?? 0);
  $cashCollected = (float) ($displaySummary['cash_collected'] ?? 0);
  $mobileCollected = (float) ($displaySummary['mobile_collected'] ?? 0);
  $bankCollected = (float) ($displaySummary['bank_collected'] ?? 0);
  $creditRecorded = (float) ($displaySummary['credit_recorded'] ?? 0);
  $expensesCount = (int) ($displaySummary['expenses_count'] ?? 0);
  $expensesTotal = (float) ($displaySummary['expenses_total'] ?? 0);
  $totalCollected = (float) ($displaySummary['total_collected'] ?? 0);
  $netInHand = (float) ($displaySummary['net_in_hand'] ?? ($totalCollected - $expensesTotal));
@endphp
<div class="day-close-summary-wrap">
<table class="table table-bordered mb-0 day-close-summary-table">
  <tbody>
    @if($salesCount > 0)
      <tr>
        <th style="width:45%">Sales recorded</th>
        <td>{{ number_format($salesCount) }} ({{ number_format($displaySummary['sales_total'] ?? 0, 0) }} TZS)</td>
      </tr>
    @endif
    @if($paidSalesCount > 0)
      <tr>
        <th>Paid sales</th>
        <td>{{ number_format($paidSalesCount) }}</td>
      </tr>
    @endif
    @if($pendingSalesCount > 0)
      <tr>
        <th>Pending / partial sales</th>
        <td>{{ number_format($pendingSalesCount) }} ({{ number_format($displaySummary['pending_sales_total'] ?? 0, 0) }} TZS due)</td>
      </tr>
    @endif
    @if($paymentsCount > 0)
      <tr>
        <th>Payments recorded</th>
        <td>{{ number_format($paymentsCount) }}</td>
      </tr>
    @endif
    @if($cashCollected > 0)
      <tr>
        <th>Cash collected</th>
        <td>{{ number_format($cashCollected, 0) }} TZS</td>
      </tr>
    @endif
    @if($mobileCollected > 0)
      <tr>
        <th>Mobile collected</th>
        <td>{{ number_format($mobileCollected, 0) }} TZS</td>
      </tr>
      @foreach($displaySummary['mobile_providers'] ?? [] as $provider)
        @if(($provider['amount'] ?? 0) > 0)
          <tr class="day-close-provider-row">
            <th class="pl-4 text-muted font-weight-normal">
              <i class="fa fa-mobile"></i> {{ $provider['name'] }}
            </th>
            <td>
              {{ number_format($provider['amount'] ?? 0, 0) }} TZS
              <small class="text-muted">({{ number_format($provider['count'] ?? 0) }} {{ Str::plural('payment', $provider['count'] ?? 0) }})</small>
            </td>
          </tr>
        @endif
      @endforeach
    @endif
    @if($bankCollected > 0)
      <tr>
        <th>Bank collected</th>
        <td>{{ number_format($bankCollected, 0) }} TZS</td>
      </tr>
      @foreach($displaySummary['bank_providers'] ?? [] as $provider)
        @if(($provider['amount'] ?? 0) > 0)
          <tr class="day-close-provider-row">
            <th class="pl-4 text-muted font-weight-normal">
              <i class="fa fa-university"></i> {{ $provider['name'] }}
            </th>
            <td>
              {{ number_format($provider['amount'] ?? 0, 0) }} TZS
              <small class="text-muted">({{ number_format($provider['count'] ?? 0) }} {{ Str::plural('payment', $provider['count'] ?? 0) }})</small>
            </td>
          </tr>
        @endif
      @endforeach
    @endif
    @if($creditRecorded > 0)
      <tr>
        <th>Credit recorded</th>
        <td>{{ number_format($creditRecorded, 0) }} TZS</td>
      </tr>
    @endif
    <tr class="day-close-summary-row--total">
      <th><strong>Total money in</strong></th>
      <td><strong>{{ number_format($totalCollected, 0) }} TZS</strong></td>
    </tr>
    @if($expensesTotal > 0 || $expensesCount > 0)
      <tr>
        <th>Expenses ({{ number_format($expensesCount) }})</th>
        <td>{{ number_format($expensesTotal, 0) }} TZS</td>
      </tr>
      @foreach($displaySummary['expenses_by_category'] ?? [] as $expenseCategory)
        @if(($expenseCategory['amount'] ?? 0) > 0)
          <tr class="day-close-provider-row">
            <th class="pl-4 text-muted font-weight-normal">
              <i class="fa fa-tag"></i> {{ $expenseCategory['label'] }}
            </th>
            <td>
              {{ number_format($expenseCategory['amount'] ?? 0, 0) }} TZS
              <small class="text-muted">({{ number_format($expenseCategory['count'] ?? 0) }} {{ Str::plural('item', $expenseCategory['count'] ?? 0) }})</small>
            </td>
          </tr>
        @endif
      @endforeach
    @endif
    <tr class="day-close-summary-row--total day-close-summary-row--net">
      <th><strong>Net in hand</strong></th>
      <td><strong>{{ number_format($netInHand, 0) }} TZS</strong></td>
    </tr>
  </tbody>
</table>
</div>

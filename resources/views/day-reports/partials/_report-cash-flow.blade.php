@php
  $moneyIn = (float) ($summary['total_collected'] ?? 0);
  $expensesOut = (float) ($summary['expenses_total'] ?? 0);
  $purchasesOut = (float) ($report['receivings_total'] ?? 0);
  $moneyOut = round($expensesOut + $purchasesOut, 2);
  $netCash = round($moneyIn - $moneyOut, 2);
  $dailyBreakdown = $report['daily_breakdown'] ?? collect();
@endphp

@include('day-reports.partials._report-status')

<p class="text-muted mb-4">
  Money in is payments collected. Money out is day expenses plus ingredient purchases.
</p>

@if(! $isDay && $dailyBreakdown->isNotEmpty())
  <h4 class="mb-3">By day</h4>
  <div class="table-responsive detail-table-wrap mb-4">
    <table class="table table-hover table-bordered mb-0 detail-card-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Money in</th>
          <th>Expenses</th>
          <th>Purchases</th>
          <th>Money out</th>
          <th>Net</th>
        </tr>
      </thead>
      <tbody>
        @foreach($dailyBreakdown as $day)
          @php
            $dayOut = round(($day['expenses'] ?? 0) + ($day['receivings'] ?? 0), 2);
            $dayNet = round(($day['collected'] ?? 0) - $dayOut, 2);
          @endphp
          <tr>
            <td data-label="Date">
              <a href="{{ route('day-reports.index', [
                'type' => \App\Services\DayReportService::TYPE_CASH_FLOW,
                'period' => 'day',
                'date' => $day['date'],
              ]) }}">
                {{ $day['label'] }}
              </a>
            </td>
            <td data-label="Money in">{{ number_format($day['collected'] ?? 0, 0) }} TZS</td>
            <td data-label="Expenses">{{ number_format($day['expenses'] ?? 0, 0) }} TZS</td>
            <td data-label="Purchases">{{ number_format($day['receivings'] ?? 0, 0) }} TZS</td>
            <td data-label="Money out">{{ number_format($dayOut, 0) }} TZS</td>
            <td data-label="Net">{{ number_format($dayNet, 0) }} TZS</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th>Total</th>
          <th>{{ number_format($moneyIn, 0) }} TZS</th>
          <th>{{ number_format($expensesOut, 0) }} TZS</th>
          <th>{{ number_format($purchasesOut, 0) }} TZS</th>
          <th>{{ number_format($moneyOut, 0) }} TZS</th>
          <th>{{ number_format($netCash, 0) }} TZS</th>
        </tr>
      </tfoot>
    </table>
  </div>
@endif

<div class="row">
  <div class="col-md-6 mb-4">
    <h4 class="mb-3">Money in</h4>
    <div class="table-responsive detail-table-wrap">
      <table class="table table-bordered mb-0 detail-card-table">
        <tbody>
          @if(($summary['cash_collected'] ?? 0) > 0)
            <tr>
              <th style="width:50%">Cash</th>
              <td>{{ number_format($summary['cash_collected'], 0) }} TZS</td>
            </tr>
          @endif
          @if(($summary['mobile_collected'] ?? 0) > 0)
            <tr>
              <th>Mobile money</th>
              <td>{{ number_format($summary['mobile_collected'], 0) }} TZS</td>
            </tr>
            @foreach($summary['mobile_providers'] ?? [] as $provider)
              @if(($provider['amount'] ?? 0) > 0)
                <tr class="day-close-provider-row">
                  <th class="pl-4 text-muted font-weight-normal">{{ $provider['name'] }}</th>
                  <td>{{ number_format($provider['amount'], 0) }} TZS</td>
                </tr>
              @endif
            @endforeach
          @endif
          @if(($summary['bank_collected'] ?? 0) > 0)
            <tr>
              <th>Bank</th>
              <td>{{ number_format($summary['bank_collected'], 0) }} TZS</td>
            </tr>
            @foreach($summary['bank_providers'] ?? [] as $provider)
              @if(($provider['amount'] ?? 0) > 0)
                <tr class="day-close-provider-row">
                  <th class="pl-4 text-muted font-weight-normal">{{ $provider['name'] }}</th>
                  <td>{{ number_format($provider['amount'], 0) }} TZS</td>
                </tr>
              @endif
            @endforeach
          @endif
          @if($moneyIn <= 0)
            <tr>
              <td colspan="2" class="text-muted text-center py-3">No money collected in this period.</td>
            </tr>
          @else
            <tr>
              <th><strong>Total money in</strong></th>
              <td><strong>{{ number_format($moneyIn, 0) }} TZS</strong></td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-md-6 mb-4">
    <h4 class="mb-3">Money out</h4>
    <div class="table-responsive detail-table-wrap">
      <table class="table table-bordered mb-0 detail-card-table">
        <tbody>
          @if($expensesOut > 0)
            <tr>
              <th style="width:50%">Day expenses</th>
              <td>{{ number_format($expensesOut, 0) }} TZS</td>
            </tr>
            @foreach($summary['expenses_by_category'] ?? [] as $row)
              @if(($row['amount'] ?? 0) > 0)
                <tr class="day-close-provider-row">
                  <th class="pl-4 text-muted font-weight-normal">{{ $row['label'] }}</th>
                  <td>{{ number_format($row['amount'], 0) }} TZS</td>
                </tr>
              @endif
            @endforeach
          @endif
          @if($purchasesOut > 0)
            <tr>
              <th>Ingredient purchases</th>
              <td>{{ number_format($purchasesOut, 0) }} TZS</td>
            </tr>
          @endif
          @if($moneyOut <= 0)
            <tr>
              <td colspan="2" class="text-muted text-center py-3">No money out in this period.</td>
            </tr>
          @else
            <tr>
              <th><strong>Total money out</strong></th>
              <td><strong>{{ number_format($moneyOut, 0) }} TZS</strong></td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="alert {{ $netCash >= 0 ? 'alert-success' : 'alert-warning' }} mb-0">
  <strong>Net cash:</strong> {{ number_format($netCash, 0) }} TZS
  <span class="text-muted">(money in − money out)</span>
</div>

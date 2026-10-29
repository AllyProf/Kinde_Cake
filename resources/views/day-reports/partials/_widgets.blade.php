@php
  $summary = $report['summary'] ?? [];
  $cashFlowMoneyIn = (float) ($summary['total_collected'] ?? 0);
  $cashFlowMoneyOut = round((float) ($summary['expenses_total'] ?? 0) + (float) ($report['receivings_total'] ?? 0), 2);
  $cashFlowNet = round($cashFlowMoneyIn - $cashFlowMoneyOut, 2);
@endphp

<div class="row dashboard-stat-cards mb-3 day-report-widgets">
  @if($reportType === \App\Services\DayReportService::TYPE_CASH_FLOW)
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Money in',
        'value' => number_format($cashFlowMoneyIn, 0).' TZS',
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Money out',
        'value' => number_format($cashFlowMoneyOut, 0).' TZS',
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Net cash',
        'value' => number_format($cashFlowNet, 0).' TZS',
      ])
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_COLLECTIONS)
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Total collected',
        'value' => number_format($summary['total_collected'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Cash',
        'value' => number_format($summary['cash_collected'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Mobile money',
        'value' => number_format($summary['mobile_collected'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Bank',
        'value' => number_format($summary['bank_collected'] ?? 0, 0).' TZS',
      ])
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_SALES)
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Sales',
        'value' => number_format($summary['sales_count'] ?? 0),
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Sales total',
        'value' => number_format($summary['sales_total'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Paid sales',
        'value' => number_format($summary['paid_sales_count'] ?? 0),
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Outstanding',
        'value' => number_format($summary['pending_sales_total'] ?? 0, 0).' TZS',
      ])
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_EXPENSES)
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Expenses',
        'value' => number_format($summary['expenses_total'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Entries',
        'value' => number_format($summary['expenses_count'] ?? 0),
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Collected (period)',
        'value' => number_format($summary['total_collected'] ?? 0, 0).' TZS',
      ])
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_PURCHASES)
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Purchase cost',
        'value' => number_format($report['receivings_total'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Receivings',
        'value' => number_format($report['receivings_count'] ?? 0),
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Net in hand',
        'value' => number_format($report['net_in_hand'] ?? 0, 0).' TZS',
      ])
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_STAFF)
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Staff closes',
        'value' => $report['staff_closes']->count(),
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Collected',
        'value' => number_format($summary['total_collected'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-4 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Expenses',
        'value' => number_format($summary['expenses_total'] ?? 0, 0).' TZS',
      ])
    </div>
  @else
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Collected',
        'value' => number_format($summary['total_collected'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Expenses',
        'value' => number_format($summary['expenses_total'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Ingredient purchases',
        'value' => number_format($report['receivings_total'] ?? 0, 0).' TZS',
      ])
    </div>
    <div class="col-md-3 col-sm-6">
      @include('partials._stat-card', [
        'label' => 'Balance',
        'value' => number_format($report['balance_after_purchases'] ?? 0, 0).' TZS',
      ])
    </div>
  @endif
</div>

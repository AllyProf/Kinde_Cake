@php
  $summary = $report['summary'] ?? [];
@endphp

<div class="row mb-3 day-report-widgets">
  @if($reportType === \App\Services\DayReportService::TYPE_COLLECTIONS)
    <div class="col-md-3 col-sm-6">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Total collected</h4>
          <p><b>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Cash</h4>
          <p><b>{{ number_format($summary['cash_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-mobile fa-3x"></i>
        <div class="info">
          <h4>Mobile money</h4>
          <p><b>{{ number_format($summary['mobile_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small warning coloured-icon">
        <i class="icon fa fa-university fa-3x"></i>
        <div class="info">
          <h4>Bank</h4>
          <p><b>{{ number_format($summary['bank_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_SALES)
    <div class="col-md-3 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-shopping-cart fa-3x"></i>
        <div class="info">
          <h4>Sales</h4>
          <p><b>{{ number_format($summary['sales_count'] ?? 0) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-line-chart fa-3x"></i>
        <div class="info">
          <h4>Sales total</h4>
          <p><b>{{ number_format($summary['sales_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-check-circle fa-3x"></i>
        <div class="info">
          <h4>Paid sales</h4>
          <p><b>{{ number_format($summary['paid_sales_count'] ?? 0) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small warning coloured-icon">
        <i class="icon fa fa-clock-o fa-3x"></i>
        <div class="info">
          <h4>Outstanding</h4>
          <p><b>{{ number_format($summary['pending_sales_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_EXPENSES)
    <div class="col-md-4 col-sm-6">
      <div class="widget-small danger coloured-icon">
        <i class="icon fa fa-minus-circle fa-3x"></i>
        <div class="info">
          <h4>Expenses</h4>
          <p><b>{{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-list fa-3x"></i>
        <div class="info">
          <h4>Entries</h4>
          <p><b>{{ number_format($summary['expenses_count'] ?? 0) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Collected (period)</h4>
          <p><b>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_PURCHASES)
    <div class="col-md-4 col-sm-6">
      <div class="widget-small warning coloured-icon">
        <i class="icon fa fa-truck fa-3x"></i>
        <div class="info">
          <h4>Purchase cost</h4>
          <p><b>{{ number_format($report['receivings_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-cubes fa-3x"></i>
        <div class="info">
          <h4>Receivings</h4>
          <p><b>{{ number_format($report['receivings_count'] ?? 0) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-hand-paper-o fa-3x"></i>
        <div class="info">
          <h4>Net in hand</h4>
          <p><b>{{ number_format($report['net_in_hand'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @elseif($reportType === \App\Services\DayReportService::TYPE_STAFF)
    <div class="col-md-4 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-users fa-3x"></i>
        <div class="info">
          <h4>Staff closes</h4>
          <p><b>{{ $report['staff_closes']->count() }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Collected</h4>
          <p><b>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="widget-small danger coloured-icon">
        <i class="icon fa fa-minus-circle fa-3x"></i>
        <div class="info">
          <h4>Expenses</h4>
          <p><b>{{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @else
    <div class="col-md-3 col-sm-6">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Collected</h4>
          <p><b>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small danger coloured-icon">
        <i class="icon fa fa-minus-circle fa-3x"></i>
        <div class="info">
          <h4>Expenses</h4>
          <p><b>{{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small warning coloured-icon">
        <i class="icon fa fa-truck fa-3x"></i>
        <div class="info">
          <h4>Ingredient purchases</h4>
          <p><b>{{ number_format($report['receivings_total'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-hand-paper-o fa-3x"></i>
        <div class="info">
          <h4>Balance</h4>
          <p><b>{{ number_format($report['balance_after_purchases'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
  @endif
</div>

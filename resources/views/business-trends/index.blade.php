@extends('layouts.app')

@section('title', 'Business Trends')

@section('content')
  @php
    $summary = $report['summary'] ?? [];
    $hasData = $report['has_data'] ?? false;
    $isDay = $period === \App\Services\DayReportService::PERIOD_DAY;
    $isWeek = $period === \App\Services\DayReportService::PERIOD_WEEK;
    $isMonth = $period === \App\Services\DayReportService::PERIOD_MONTH;
    $periodLabel = $isDay ? 'Daily' : ($isWeek ? 'Weekly' : 'Monthly');
    $noDataMessage = $isDay
      ? 'No sales, expenses, or activity were recorded for '.$selectedDate->format('d M Y').'.'
      : 'No activity was recorded for '.$report['period_label'].'.';
    $mobileProviders = collect($summary['mobile_providers'] ?? []);
    $bankProviders = collect($summary['bank_providers'] ?? []);
    $showTrendLine = ! $isDay || count($charts['daily_trend']['labels'] ?? []) > 1;
  @endphp

  <div class="app-title">
    <div>
      <h1><i class="fa fa-line-chart"></i> Business Trends</h1>
      <p>Visual charts for sales, collections, expenses, and top-performing items.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Business Trends</li>
    </ul>
  </div>

  <div class="row mb-3">
    <div class="col-md-12">
      <div class="tile">
        <div class="tile-body">
          <ul class="nav nav-pills mb-3">
            <li class="nav-item">
              <a class="nav-link {{ $isDay ? 'active' : '' }}"
                href="{{ route('business-trends.index', ['period' => 'day', 'date' => $selectedDate->format('Y-m-d')]) }}">
                Daily
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $isWeek ? 'active' : '' }}"
                href="{{ route('business-trends.index', ['period' => 'week', 'date' => $selectedDate->format('Y-m-d')]) }}">
                Weekly
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $isMonth ? 'active' : '' }}"
                href="{{ route('business-trends.index', ['period' => 'month', 'month' => $selectedDate->format('Y-m')]) }}">
                Monthly
              </a>
            </li>
          </ul>

          <form method="GET" action="{{ route('business-trends.index') }}" class="business-trends-filters">
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="row align-items-end">
              <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                @if($isMonth)
                  <label class="control-label" for="trendsMonth">Month</label>
                  <input type="month" class="form-control" id="trendsMonth" name="month"
                    value="{{ $selectedDate->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
                @else
                  <label class="control-label" for="trendsDate">
                    {{ $isWeek ? 'Week containing' : 'Date' }}
                  </label>
                  <input type="date" class="form-control" id="trendsDate" name="date"
                    value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                  @if($isWeek)
                    <small class="text-muted d-block mt-1">{{ $range['label'] }}</small>
                  @endif
                @endif
              </div>
              <div class="col-12 col-md-8 col-lg-5 business-trends-filter-actions">
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-search"></i> View trends
                </button>
                <a href="{{ route('day-reports.index', request()->only(['period', 'date', 'month'])) }}" class="btn btn-outline-secondary">
                  <i class="fa fa-table"></i> Table report
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @if($hasData)
    <div class="row mb-3 business-trends-widgets">
      <div class="col-12 col-md-3 col-sm-6">
        <div class="widget-small success coloured-icon">
          <i class="icon fa fa-money fa-3x"></i>
          <div class="info">
            <h4>Collected</h4>
            <p><b>{{ number_format($summary['total_collected'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 col-sm-6">
        <div class="widget-small primary coloured-icon">
          <i class="icon fa fa-shopping-cart fa-3x"></i>
          <div class="info">
            <h4>Sales total</h4>
            <p><b>{{ number_format($summary['sales_total'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 col-sm-6">
        <div class="widget-small danger coloured-icon">
          <i class="icon fa fa-minus-circle fa-3x"></i>
          <div class="info">
            <h4>Expenses</h4>
            <p><b>{{ number_format($summary['expenses_total'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-3 col-sm-6">
        <div class="widget-small info coloured-icon">
          <i class="icon fa fa-line-chart fa-3x"></i>
          <div class="info">
            <h4>Net in hand</h4>
            <p><b>{{ number_format($report['net_in_hand'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      </div>
    </div>

    <div class="row trends-chart-row">
      <div class="col-lg-8">
        <div class="tile h-100">
          <h3 class="tile-title">{{ $periodLabel }} money trend — {{ $report['period_label'] }}</h3>
          <div class="tile-body">
            <div class="trends-chart-frame">
              @if($showTrendLine)
                <canvas id="moneyTrendChart"></canvas>
              @else
                <canvas id="moneyFlowChart"></canvas>
              @endif
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="tile h-100">
          <h3 class="tile-title">Payment methods</h3>
          <div class="tile-body">
            @if(count($charts['payment_methods']) > 0)
              <div class="trends-chart-frame trends-chart-frame--round">
                <canvas id="paymentMethodsChart"></canvas>
              </div>
            @else
              <div class="trends-chart-empty">No payments recorded for this period.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row trends-chart-row">
      <div class="col-lg-6">
        <div class="tile h-100">
          <h3 class="tile-title">Sales vs collected</h3>
          <div class="tile-body">
            <div class="trends-chart-frame">
              <canvas id="salesTrendChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="tile h-100">
          <h3 class="tile-title">Top selling items</h3>
          <div class="tile-body">
            @if(count($charts['top_items']['labels'] ?? []) > 0)
              <div class="trends-chart-frame">
                <canvas id="topItemsChart"></canvas>
              </div>
            @else
              <div class="trends-chart-empty">No item sales in this period.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row trends-chart-row">
      <div class="col-lg-4">
        <div class="tile h-100">
          <h3 class="tile-title">Expenses by category</h3>
          <div class="tile-body">
            @if(count($charts['expense_categories']) > 0)
              <div class="trends-chart-frame trends-chart-frame--round">
                <canvas id="expenseCategoriesChart"></canvas>
              </div>
            @else
              <div class="trends-chart-empty">No expenses recorded for this period.</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="tile h-100">
          <h3 class="tile-title">Sales payment status</h3>
          <div class="tile-body">
            @if(count($charts['sales_status']) > 0)
              <div class="trends-chart-frame trends-chart-frame--round">
                <canvas id="salesStatusChart"></canvas>
              </div>
            @else
              <div class="trends-chart-empty">No sales recorded for this period.</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="tile h-100">
          <h3 class="tile-title">Period snapshot</h3>
          <div class="tile-body">
            <div class="day-close-summary-wrap">
              <table class="table table-sm table-borderless mb-0 w-100 day-close-summary-table">
                <tbody>
                  <tr>
                    <th>Sales count</th>
                    <td>{{ number_format($summary['sales_count'] ?? 0) }}</td>
                  </tr>
                  <tr>
                    <th>Paid sales</th>
                    <td>{{ number_format($summary['paid_sales_count'] ?? 0) }}</td>
                  </tr>
                  <tr>
                    <th>Pending / partial</th>
                    <td>{{ number_format($summary['pending_sales_count'] ?? 0) }}</td>
                  </tr>
                  <tr>
                    <th>Outstanding debt</th>
                    <td>{{ number_format($summary['pending_sales_total'] ?? 0, 0) }} TZS</td>
                  </tr>
                  <tr>
                    <th>Payments recorded</th>
                    <td>{{ number_format($summary['payments_count'] ?? 0) }}</td>
                  </tr>
                  <tr>
                    <th>Ingredient purchases</th>
                    <td>{{ number_format($report['receivings_total'] ?? 0, 0) }} TZS</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if($mobileProviders->isNotEmpty() || $bankProviders->isNotEmpty())
      <div class="row trends-chart-row">
        @if($mobileProviders->isNotEmpty())
          <div class="col-lg-6">
            <div class="tile h-100">
              <h3 class="tile-title">Mobile money providers</h3>
              <div class="tile-body">
                <div class="trends-chart-frame">
                  <canvas id="mobileProvidersChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        @endif
        @if($bankProviders->isNotEmpty())
          <div class="col-lg-6">
            <div class="tile h-100">
              <h3 class="tile-title">Bank providers</h3>
              <div class="tile-body">
                <div class="trends-chart-frame">
                  <canvas id="bankProvidersChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        @endif
      </div>
    @endif
  @else
    <div class="row">
      <div class="col-md-12">
        <div class="tile">
          <div class="tile-body text-center py-5">
            <i class="fa fa-line-chart fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No data found</h4>
            <p class="text-muted mb-0">{{ $noDataMessage }}</p>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@if($hasData)
  @push('styles')
    <style>
      .trends-chart-row {
        display: flex;
        flex-wrap: wrap;
      }

      .trends-chart-row > [class*="col-"] {
        display: flex;
        margin-bottom: 1rem;
      }

      .trends-chart-row .tile {
        width: 100%;
      }

      .trends-chart-frame {
        position: relative;
        width: 100%;
      }

      .trends-chart-frame canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
      }

      .trends-chart-frame--round {
        margin: 0 auto;
      }

      .trends-chart-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        text-align: center;
        color: #6c757d;
      }

      @media (min-width: 768px) {
        .trends-chart-frame {
          height: 280px;
        }

        .trends-chart-frame--round {
          width: 280px;
          max-width: 100%;
        }

        .trends-chart-empty {
          height: 280px;
          padding: 1.5rem;
        }
      }
    </style>
  @endpush

  @push('scripts')
    <script src="{{ asset('panel-assets/js/plugins/chart.js') }}"></script>
    <script>
      (function () {
        var charts = @json($charts);
        var showTrendLine = @json($showTrendLine);
        var mobileProviders = @json($mobileProviders->values());
        var bankProviders = @json($bankProviders->values());

        var chartDefaults = {
          responsive: true,
          maintainAspectRatio: false
        };

        function lineDataset(label, data, strokeColor, fillColor) {
          return {
            label: label,
            fillColor: fillColor,
            strokeColor: strokeColor,
            pointColor: strokeColor,
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: strokeColor,
            data: data
          };
        }

        function barDataset(label, data, fillColor, strokeColor) {
          return {
            label: label,
            fillColor: fillColor,
            strokeColor: strokeColor,
            highlightFill: fillColor,
            highlightStroke: strokeColor,
            data: data
          };
        }

        function formatMoney(value) {
          return Number(value || 0).toLocaleString() + ' TZS';
        }

        if (showTrendLine) {
          var trendCtx = document.getElementById('moneyTrendChart').getContext('2d');
          new Chart(trendCtx).Line({
            labels: charts.daily_trend.labels,
            datasets: [
              lineDataset('Collected', charts.daily_trend.collected, 'rgba(40, 167, 69, 1)', 'rgba(40, 167, 69, 0.15)'),
              lineDataset('Expenses', charts.daily_trend.expenses, 'rgba(220, 53, 69, 1)', 'rgba(220, 53, 69, 0.12)'),
              lineDataset('Net in hand', charts.daily_trend.net, 'rgba(0, 123, 255, 1)', 'rgba(0, 123, 255, 0.12)')
            ]
          }, Object.assign({}, chartDefaults, {
            multiTooltipTemplate: function (item) {
              return item.label + ': ' + formatMoney(item.value);
            }
          }));
        } else {
          var flowCtx = document.getElementById('moneyFlowChart').getContext('2d');
          new Chart(flowCtx).Bar({
            labels: ['Today'],
            datasets: [
              barDataset('Collected', [charts.daily_trend.collected[0] || 0], 'rgba(40, 167, 69, 0.85)', 'rgba(40, 167, 69, 1)'),
              barDataset('Expenses', [charts.daily_trend.expenses[0] || 0], 'rgba(220, 53, 69, 0.85)', 'rgba(220, 53, 69, 1)'),
              barDataset('Net in hand', [charts.daily_trend.net[0] || 0], 'rgba(0, 123, 255, 0.85)', 'rgba(0, 123, 255, 1)')
            ]
          }, Object.assign({}, chartDefaults, {
            multiTooltipTemplate: function (item) {
              return item.datasetLabel + ': ' + formatMoney(item.value);
            }
          }));
        }

        var salesCtx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(salesCtx).Bar({
          labels: charts.daily_trend.labels,
          datasets: [
            barDataset('Sales total', charts.daily_trend.sales_total, 'rgba(111, 66, 193, 0.75)', 'rgba(111, 66, 193, 1)'),
            barDataset('Collected', charts.daily_trend.collected, 'rgba(40, 167, 69, 0.75)', 'rgba(40, 167, 69, 1)')
          ]
        }, Object.assign({}, chartDefaults, {
          multiTooltipTemplate: function (item) {
            return item.datasetLabel + ': ' + formatMoney(item.value);
          }
        }));

        if (charts.payment_methods.length) {
          var paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
          new Chart(paymentCtx).Doughnut(charts.payment_methods, Object.assign({}, chartDefaults, {
            tooltipTemplate: function (item) {
              return item.label + ': ' + formatMoney(item.value);
            }
          }));
        }

        if (charts.expense_categories.length) {
          var expenseCtx = document.getElementById('expenseCategoriesChart').getContext('2d');
          new Chart(expenseCtx).Pie(charts.expense_categories, Object.assign({}, chartDefaults, {
            tooltipTemplate: function (item) {
              return item.label + ': ' + formatMoney(item.value);
            }
          }));
        }

        if (charts.sales_status.length) {
          var statusCtx = document.getElementById('salesStatusChart').getContext('2d');
          new Chart(statusCtx).Doughnut(charts.sales_status, Object.assign({}, chartDefaults, {
            tooltipTemplate: function (item) {
              return item.label + ': ' + Number(item.value).toLocaleString();
            }
          }));
        }

        if (charts.top_items.labels.length) {
          var topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
          new Chart(topItemsCtx).Bar({
            labels: charts.top_items.labels,
            datasets: [{
              label: 'Revenue (TZS)',
              fillColor: 'rgba(0, 123, 255, 0.75)',
              strokeColor: 'rgba(0, 123, 255, 1)',
              highlightFill: 'rgba(0, 123, 255, 0.9)',
              highlightStroke: 'rgba(0, 123, 255, 1)',
              data: charts.top_items.revenue
            }]
          }, Object.assign({}, chartDefaults, {
            multiTooltipTemplate: function (item) {
              return formatMoney(item.value);
            }
          }));
        }

        if (mobileProviders.length) {
          var mobileCtx = document.getElementById('mobileProvidersChart').getContext('2d');
          new Chart(mobileCtx).Bar({
            labels: mobileProviders.map(function (row) { return row.name; }),
            datasets: [{
              label: 'Collected',
              fillColor: 'rgba(0, 123, 255, 0.75)',
              strokeColor: 'rgba(0, 123, 255, 1)',
              highlightFill: 'rgba(0, 123, 255, 0.9)',
              highlightStroke: 'rgba(0, 123, 255, 1)',
              data: mobileProviders.map(function (row) { return row.amount; })
            }]
          }, Object.assign({}, chartDefaults, {
            multiTooltipTemplate: function (item) {
              return formatMoney(item.value);
            }
          }));
        }

        if (bankProviders.length) {
          var bankCtx = document.getElementById('bankProvidersChart').getContext('2d');
          new Chart(bankCtx).Bar({
            labels: bankProviders.map(function (row) { return row.name; }),
            datasets: [{
              label: 'Collected',
              fillColor: 'rgba(111, 66, 193, 0.75)',
              strokeColor: 'rgba(111, 66, 193, 1)',
              highlightFill: 'rgba(111, 66, 193, 0.9)',
              highlightStroke: 'rgba(111, 66, 193, 1)',
              data: bankProviders.map(function (row) { return row.amount; })
            }]
          }, Object.assign({}, chartDefaults, {
            multiTooltipTemplate: function (item) {
              return formatMoney(item.value);
            }
          }));
        }
      })();
    </script>
  @endpush
@endif

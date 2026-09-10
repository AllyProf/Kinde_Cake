@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  @php
    $stats = $stats ?? [];
    $todayLabel = $today->format('l, d M Y');
  @endphp

  <div class="app-title">
    <div>
      <h1><i class="fa fa-dashboard"></i> Dashboard</h1>
      <p>Welcome back, <strong>{{ auth()->user()->name }}</strong> · {{ $todayLabel }}</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item">Dashboard</li>
    </ul>
  </div>

  @foreach($alerts as $alert)
    <div class="alert alert-{{ $alert['type'] }} mb-3">
      <i class="fa fa-{{ $alert['type'] === 'danger' ? 'exclamation-circle' : ($alert['type'] === 'success' ? 'check-circle' : 'info-circle') }}"></i>
      {{ $alert['message'] }}
      @if(! empty($alert['link']))
        <a href="{{ $alert['link'] }}" class="alert-link ml-1">{{ $alert['label'] ?? 'View' }}</a>
      @endif
    </div>
  @endforeach

  <div class="row mb-3">
    <div class="col-md-6 col-lg-3">
      <div class="widget-small success coloured-icon">
        <i class="icon fa fa-money fa-3x"></i>
        <div class="info">
          <h4>Collected today</h4>
          <p><b>{{ number_format($stats['today_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="widget-small primary coloured-icon">
        <i class="icon fa fa-shopping-cart fa-3x"></i>
        <div class="info">
          <h4>Sales today</h4>
          <p><b>{{ number_format($stats['today_sales_count'] ?? 0) }}</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="widget-small info coloured-icon">
        <i class="icon fa fa-calendar fa-3x"></i>
        <div class="info">
          <h4>{{ $is_owner ? 'Month collected' : 'My month collected' }}</h4>
          <p><b>{{ number_format($stats['month_collected'] ?? 0, 0) }} TZS</b></p>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      @if($has_sales_access)
        <div class="widget-small warning coloured-icon">
          <i class="icon fa fa-credit-card fa-3x"></i>
          <div class="info">
            <h4>Outstanding debt</h4>
            <p><b>{{ number_format($stats['outstanding_debt'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      @else
        <div class="widget-small danger coloured-icon">
          <i class="icon fa fa-minus-circle fa-3x"></i>
          <div class="info">
            <h4>Expenses today</h4>
            <p><b>{{ number_format($stats['today_expenses'] ?? 0, 0) }} TZS</b></p>
          </div>
        </div>
      @endif
    </div>
  </div>

  @if($has_sales_access)
    <div class="row dashboard-chart-row">
      <div class="col-lg-8">
        <div class="tile h-100">
          <h3 class="tile-title">Last 7 days — money collected</h3>
          <div class="tile-body">
            <div class="dashboard-chart-frame">
              <canvas id="dashboardWeekChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="tile h-100">
          <h3 class="tile-title">Today's payments</h3>
          <div class="tile-body d-flex align-items-center justify-content-center">
            @if(count($payment_methods) > 0)
              <div class="dashboard-chart-frame dashboard-chart-frame--round">
                <canvas id="dashboardPaymentsChart"></canvas>
              </div>
            @else
              <div class="dashboard-chart-empty">No payments recorded today yet.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    @if($has_sales_access)
      <div class="col-lg-8">
        <div class="tile">
          <div class="tile-title-w-btn">
            <h3 class="title">Recent sales</h3>
            <p>
              <a class="btn btn-primary btn-sm" href="{{ route('sales.index') }}">
                <i class="fa fa-list"></i> All sales
              </a>
              @if($can_create_sale)
                <a class="btn btn-outline-primary btn-sm" href="{{ route('sales.create') }}">
                  <i class="fa fa-plus"></i> New sale
                </a>
              @endif
            </p>
          </div>
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered mb-0">
                <thead>
                  <tr>
                    <th>Sale #</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($recent_sales as $sale)
                    <tr>
                      <td>
                        <a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a>
                      </td>
                      <td>{{ $sale->customer_name ?: ($sale->customer?->name ?? 'Walk-in') }}</td>
                      <td>{{ number_format($sale->total, 0) }} TZS</td>
                      <td>
                        <span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span>
                      </td>
                      <td>{{ $sale->sold_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No sales recorded yet.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif

    <div class="{{ $has_sales_access ? 'col-lg-4' : 'col-lg-12' }}">
      <div class="tile">
        <h3 class="tile-title">Quick actions</h3>
        <div class="tile-body">
          <div class="list-group">
            @if($can_create_sale)
              <a href="{{ route('sales.create') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-plus-circle text-primary"></i> New sale
              </a>
            @endif
            @if($has_sales_access)
              <a href="{{ route('debts.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-credit-card text-warning"></i> Debt management
                @if(($stats['open_debts_count'] ?? 0) > 0)
                  <span class="badge badge-warning float-right">{{ $stats['open_debts_count'] }}</span>
                @endif
              </a>
              <a href="{{ route('cake-point.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-birthday-cake text-info"></i> Orders
                @if(($stats['active_cake_point'] ?? 0) > 0)
                  <span class="badge badge-info float-right">{{ $stats['active_cake_point'] }}</span>
                @endif
              </a>
            @endif
            @if($can_close_day)
              <a href="{{ route('day-closes.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-calendar-check-o text-success"></i> Close day
                @if(! $personal_day_closed)
                  <span class="badge badge-secondary float-right">Open</span>
                @else
                  <span class="badge badge-success float-right">Done</span>
                @endif
              </a>
            @endif
            @if($is_owner)
              <a href="{{ route('business-trends.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-line-chart text-primary"></i> Business trends
              </a>
              <a href="{{ route('day-reports.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-bar-chart text-primary"></i> Reports
              </a>
            @endif
            @if($has_inventory_access)
              <a href="{{ route('stock.index') }}" class="list-group-item list-group-item-action">
                <i class="fa fa-cubes text-danger"></i> Stock
                @if(($stats['low_stock_count'] ?? 0) + ($stats['out_of_stock_count'] ?? 0) > 0)
                  <span class="badge badge-danger float-right">{{ ($stats['low_stock_count'] ?? 0) + ($stats['out_of_stock_count'] ?? 0) }}</span>
                @endif
              </a>
            @endif
          </div>
        </div>
      </div>

      @if($has_sales_access && $top_debts->isNotEmpty())
        <div class="tile">
          <h3 class="tile-title">Top outstanding debts</h3>
          <div class="tile-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Due</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($top_debts as $sale)
                    <tr>
                      <td>
                        <a href="{{ route('sales.show', $sale) }}">{{ $sale->customer_name ?: 'Walk-in' }}</a>
                        <br><small class="text-muted">{{ number_format($sale->debtAmount(), 0) }} TZS</small>
                      </td>
                      <td>
                        @if($sale->isDebtOverdue())
                          <span class="badge badge-danger">Overdue</span>
                        @elseif($sale->credit_repayment_date)
                          {{ $sale->credit_repayment_date->format('d M Y') }}
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <a href="{{ route('debts.index') }}" class="btn btn-sm btn-outline-primary mt-3">View all debts</a>
          </div>
        </div>
      @endif

      @if($has_inventory_access && ($low_stock->isNotEmpty() || $out_of_stock->isNotEmpty()))
        <div class="tile">
          <h3 class="tile-title">Stock alerts</h3>
          <div class="tile-body">
            @if($out_of_stock->isNotEmpty())
              <h6 class="text-danger mb-2">Out of stock</h6>
              <ul class="list-unstyled mb-3">
                @foreach($out_of_stock as $ingredient)
                  <li><i class="fa fa-circle text-danger"></i> {{ $ingredient->name }}</li>
                @endforeach
              </ul>
            @endif
            @if($low_stock->isNotEmpty())
              <h6 class="text-warning mb-2">Low stock</h6>
              <ul class="list-unstyled mb-0">
                @foreach($low_stock as $ingredient)
                  <li><i class="fa fa-circle text-warning"></i> {{ $ingredient->name }}</li>
                @endforeach
              </ul>
            @endif
            <a href="{{ route('stock.index') }}" class="btn btn-sm btn-outline-primary mt-3">View stock</a>
          </div>
        </div>
      @endif

      @if($is_owner)
        <div class="tile">
          <h3 class="tile-title">Today at a glance</h3>
          <div class="tile-body">
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <th>Net in hand</th>
                <td>{{ number_format($stats['today_net'] ?? 0, 0) }} TZS</td>
              </tr>
              <tr>
                <th>Month sales</th>
                <td>{{ number_format($stats['month_sales_count'] ?? 0) }} ({{ number_format($stats['month_sales_total'] ?? 0, 0) }} TZS)</td>
              </tr>
              <tr>
                <th>Business day</th>
                <td>
                  @if($business_day_closed)
                    <span class="badge badge-success">Closed</span>
                  @else
                    <span class="badge badge-secondary">Open</span>
                  @endif
                </td>
              </tr>
              @if($pending_staff_count > 0)
                <tr>
                  <th>Staff pending</th>
                  <td><span class="badge badge-warning">{{ $pending_staff_count }}</span></td>
                </tr>
              @endif
            </table>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection

@if($has_sales_access)
  @push('styles')
    <style>
      .dashboard-chart-row {
        display: flex;
        flex-wrap: wrap;
      }

      .dashboard-chart-row > [class*="col-"] {
        display: flex;
        margin-bottom: 1rem;
      }

      .dashboard-chart-row .tile {
        width: 100%;
      }

      .dashboard-chart-frame {
        position: relative;
        width: 100%;
        height: 280px;
      }

      .dashboard-chart-frame canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
      }

      .dashboard-chart-frame--round {
        width: 280px;
        max-width: 100%;
        margin: 0 auto;
      }

      .dashboard-chart-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 280px;
        padding: 1.5rem;
        text-align: center;
        color: #6c757d;
      }
    </style>
  @endpush

  @push('scripts')
    <script src="{{ asset('panel-assets/js/plugins/chart.js') }}"></script>
    <script>
      (function () {
        var weekTrend = @json($week_trend);
        var paymentMethods = @json($payment_methods);
        var chartDefaults = { responsive: true, maintainAspectRatio: false };

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

        function formatMoney(value) {
          return Number(value || 0).toLocaleString() + ' TZS';
        }

        var weekCtx = document.getElementById('dashboardWeekChart').getContext('2d');
        new Chart(weekCtx).Line({
          labels: weekTrend.labels,
          datasets: [
            lineDataset('Collected', weekTrend.collected, 'rgba(40, 167, 69, 1)', 'rgba(40, 167, 69, 0.15)'),
            lineDataset('Sales total', weekTrend.sales_total, 'rgba(0, 123, 255, 1)', 'rgba(0, 123, 255, 0.12)')
          ]
        }, Object.assign({}, chartDefaults, {
          multiTooltipTemplate: function (item) {
            return item.datasetLabel + ': ' + formatMoney(item.value);
          }
        }));

        if (paymentMethods.length) {
          var paymentsCtx = document.getElementById('dashboardPaymentsChart').getContext('2d');
          new Chart(paymentsCtx).Doughnut(paymentMethods, Object.assign({}, chartDefaults, {
            tooltipTemplate: function (item) {
              return item.label + ': ' + formatMoney(item.value);
            }
          }));
        }
      })();
    </script>
  @endpush
@endif

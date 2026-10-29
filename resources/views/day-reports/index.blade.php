@extends('layouts.app')

@section('title', 'Reports')

@section('content')
  @php
    $summary = $report['summary'] ?? [];
    $isDay = ($range['start'] ?? $from)->equalTo($range['end'] ?? $to);
    $isWeek = $period === \App\Services\DayReportService::PERIOD_WEEK;
    $isMonth = $period === \App\Services\DayReportService::PERIOD_MONTH;
    $isCustom = $period === \App\Services\DayReportService::PERIOD_CUSTOM;
    $periodLabel = match (true) {
      $isDay && ! $isCustom => 'Daily',
      $isWeek => 'Weekly',
      $isMonth => 'Monthly',
      default => 'Custom range',
    };
    $reportTypeLabel = $reportTypes[$reportType] ?? 'Report';
    $todayUrl = route('day-reports.index', [
      'type' => $reportType,
      'period' => 'day',
      'from' => now()->format('Y-m-d'),
      'to' => now()->format('Y-m-d'),
    ]);
    $noDataMessage = match ($reportType) {
      \App\Services\DayReportService::TYPE_CASH_FLOW => 'No money in or money out was recorded for '.$report['period_label'].'.',
      \App\Services\DayReportService::TYPE_SALES => 'No sales were recorded for '.$report['period_label'].'.',
      \App\Services\DayReportService::TYPE_EXPENSES => 'No expenses were recorded for '.$report['period_label'].'.',
      \App\Services\DayReportService::TYPE_PURCHASES => 'No ingredient receivings were recorded for '.$report['period_label'].'.',
      \App\Services\DayReportService::TYPE_STAFF => 'No staff day closes were recorded for '.$report['period_label'].'.',
      \App\Services\DayReportService::TYPE_COLLECTIONS => 'No payments were recorded for '.$report['period_label'].'.',
      default => $isDay
        ? 'No sales, expenses, or ingredient receivings were recorded for '.$selectedDate->format('d M Y').'.'
        : 'No activity was recorded for '.$report['period_label'].'.',
    };
  @endphp

  <div class="app-title">
    <div>
      <h1><i class="fa fa-bar-chart"></i> Reports</h1>
      <p>Pick a report type, set the dates, then view the numbers.</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
      <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
      <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
      <li class="breadcrumb-item">Reports</li>
    </ul>
  </div>

  <div class="row mb-3">
    <div class="col-md-12">
      <div class="tile">
        <h3 class="tile-title">Report options</h3>
        <div class="tile-body">
          @include('day-reports.partials._filters')
        </div>
      </div>
    </div>
  </div>

  @if($hasData)
    @include('day-reports.partials._widgets')
  @endif

  <div class="row">
    <div class="col-lg-9">
      <div class="tile">
        <div class="tile-title-w-btn">
          <h3 class="title">{{ $reportTypeLabel }}</h3>
          <p class="mb-0 text-muted">{{ $periodLabel }} · {{ $report['period_label'] }}</p>
        </div>
        <div class="tile-body">
          @if(! $hasData)
            <div class="text-center py-5 day-report-empty">
              <i class="fa fa-folder-open-o fa-4x text-muted mb-3"></i>
              <h4 class="text-muted">No data found</h4>
              <p class="text-muted">{{ $noDataMessage }}</p>
              @if(! $from->isToday() || ! $to->isToday())
                <a href="{{ $todayUrl }}" class="btn btn-primary mt-2">
                  <i class="fa fa-calendar"></i> View today’s report
                </a>
              @endif
              <p class="text-muted mt-3 mb-0">
                Or choose another report type above, or a closed day on the right.
              </p>
            </div>
          @else
            @include(match ($reportType) {
              \App\Services\DayReportService::TYPE_CASH_FLOW => 'day-reports.partials._report-cash-flow',
              \App\Services\DayReportService::TYPE_COLLECTIONS => 'day-reports.partials._report-collections',
              \App\Services\DayReportService::TYPE_SALES => 'day-reports.partials._report-sales',
              \App\Services\DayReportService::TYPE_EXPENSES => 'day-reports.partials._report-expenses',
              \App\Services\DayReportService::TYPE_PURCHASES => 'day-reports.partials._report-purchases',
              \App\Services\DayReportService::TYPE_STAFF => 'day-reports.partials._report-staff',
              default => 'day-reports.partials._report-summary',
            })
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-3">
      <div class="tile">
        <h3 class="tile-title">Recent closed days</h3>
        <div class="tile-body">
          <div class="table-responsive detail-table-wrap day-report-recent-wrap">
            <table class="table table-hover table-bordered mb-0 detail-card-table day-report-recent-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Net</th>
                </tr>
              </thead>
              <tbody>
                @forelse($recentDays as $close)
                  @php $closedDate = $close->business_date->format('Y-m-d'); @endphp
                  <tr>
                    <td data-label="Date">
                      <a href="{{ route('day-reports.index', [
                        'type' => $reportType,
                        'period' => 'day',
                        'from' => $closedDate,
                        'to' => $closedDate,
                      ]) }}">
                        {{ $close->formattedBusinessDate() }}
                      </a>
                    </td>
                    <td data-label="Net">{{ $close->formattedSummaryMoney('net_in_hand') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="2" class="text-center text-muted py-3">No closed days yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if($isDay)
            <div class="mt-3 day-report-sidebar-action">
              <a href="{{ route('day-closes.index', ['date' => $from->format('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm btn-block">
                <i class="fa fa-calendar-check-o"></i> Go to Close Day
              </a>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <style>
    .day-close-provider-row th {
      padding-left: 2rem !important;
      font-size: 0.92rem;
    }

    .day-close-provider-row td {
      font-size: 0.92rem;
    }
  </style>
@endpush

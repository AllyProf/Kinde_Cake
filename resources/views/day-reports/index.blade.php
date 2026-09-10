@extends('layouts.app')

@section('title', 'Reports')

@section('content')
  @php
    $summary = $report['summary'] ?? [];
    $isDay = $period === \App\Services\DayReportService::PERIOD_DAY;
    $isWeek = $period === \App\Services\DayReportService::PERIOD_WEEK;
    $isMonth = $period === \App\Services\DayReportService::PERIOD_MONTH;
    $periodLabel = $isDay ? 'Daily' : ($isWeek ? 'Weekly' : 'Monthly');
    $reportTypeLabel = $reportTypes[$reportType] ?? 'Report';
    $noDataMessage = match ($reportType) {
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
      <p>Choose a report type and period, then click View report.</p>
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
    <div class="col-lg-8">
      <div class="tile">
        <h3 class="tile-title">{{ $reportTypeLabel }} — {{ $periodLabel }} · {{ $report['period_label'] }}</h3>
        <div class="tile-body">
          @if(! $hasData)
            <div class="text-center py-5">
              <i class="fa fa-folder-open-o fa-4x text-muted mb-3"></i>
              <h4 class="text-muted">No data found</h4>
              <p class="text-muted mb-0">{{ $noDataMessage }}</p>
            </div>
          @else
            @include(match ($reportType) {
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

    <div class="col-lg-4">
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
                  <tr>
                    <td data-label="Date">
                      <a href="{{ route('day-reports.index', [
                        'type' => $reportType,
                        'period' => 'day',
                        'date' => $close->business_date->format('Y-m-d'),
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
              <a href="{{ route('day-closes.index', ['date' => $selectedDate->format('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm">
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

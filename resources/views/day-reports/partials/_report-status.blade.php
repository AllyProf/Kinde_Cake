@if($isDay && $report['is_closed'])
  <div class="alert alert-success mb-3">
    <i class="fa fa-check-circle"></i>
    Business day closed on {{ $report['day_close']->closed_at->format('d M Y H:i') }}
    by <strong>{{ $report['day_close']->closedBy?->name ?? '—' }}</strong>.
  </div>
@elseif($isDay && $report['data_source'] === 'submissions')
  <div class="alert alert-info mb-3">
    <i class="fa fa-info-circle"></i>
    Totals are based on staff day submissions. Business day is not closed yet.
  </div>
@elseif($isDay)
  <div class="alert alert-secondary mb-3">
    <i class="fa fa-clock-o"></i>
    Live report for this date. Close the business day to save a final snapshot.
  </div>
@else
  <div class="alert alert-info mb-3">
    <i class="fa fa-calendar"></i>
    Combined totals from {{ \Carbon\Carbon::parse($report['range_start'])->format('d M') }}
    to {{ \Carbon\Carbon::parse($report['range_end'])->format('d M Y') }}.
    @if(($report['closed_days_count'] ?? 0) > 0)
      {{ $report['closed_days_count'] }} business {{ Str::plural('day', $report['closed_days_count']) }} closed in this period.
    @endif
  </div>
@endif

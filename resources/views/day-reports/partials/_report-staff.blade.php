@include('day-reports.partials._report-status')

<h4 class="mb-3">Staff day closes</h4>
@if($report['staff_closes']->isNotEmpty())
  @include('day-closes.partials._staff-closes-table', [
    'staffCloses' => $report['staff_closes'],
    'fromSnapshot' => $isDay && $report['is_closed'],
    'showDate' => ! $isDay,
  ])
@else
  <p class="text-muted mb-0">No staff day closes recorded for this period.</p>
@endif

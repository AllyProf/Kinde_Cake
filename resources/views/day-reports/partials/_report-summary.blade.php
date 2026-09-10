@include('day-reports.partials._report-status')

@if(! $isDay && $report['daily_breakdown']->isNotEmpty())
  <h4 class="mb-3">Daily breakdown</h4>
  @include('day-reports.partials._daily-breakdown-table', [
    'dailyBreakdown' => $report['daily_breakdown'],
  ])
@endif

<h4 class="mb-3 {{ ! $isDay && $report['daily_breakdown']->isNotEmpty() ? 'mt-4' : '' }}">Money collected</h4>
@include('day-closes.partials._summary-table', ['displaySummary' => $summary])

<div class="mt-4">
  <h4 class="mb-3">Expenses used ({{ number_format($summary['expenses_count'] ?? 0) }})</h4>
  @if($report['expenses']->isNotEmpty())
    @include('day-closes.partials._expenses-table', [
      'expenses' => $report['expenses'],
      'editable' => false,
      'showDate' => ! $isDay,
    ])
  @else
    <p class="text-muted mb-0">No expenses recorded for this period.</p>
  @endif
</div>

<div class="mt-4">
  <h4 class="mb-3">Ingredient receivings ({{ number_format($report['receivings_count'] ?? 0) }})</h4>
  @if($report['receivings']->isNotEmpty())
    @include('day-reports.partials._receivings-table', ['receivings' => $report['receivings']])
    <p class="mb-0 mt-2">
      <strong>Total purchase cost:</strong> {{ number_format($report['receivings_total'], 0) }} TZS
    </p>
  @else
    <p class="text-muted mb-0">No ingredient receivings recorded for this period.</p>
  @endif
</div>

@if($isDay && $report['staff_closes']->isNotEmpty())
  <div class="mt-4">
    <h4 class="mb-3">Personal day closes</h4>
    @include('day-closes.partials._staff-closes-table', [
      'staffCloses' => $report['staff_closes'],
      'fromSnapshot' => $report['is_closed'],
    ])
  </div>
@endif

@include('day-reports.partials._balance-summary')

<form method="GET" action="{{ route('day-reports.index') }}" class="form-row align-items-end day-report-filters" id="dayReportFilters">
  <div class="form-group col-12 col-md-4 col-lg-3">
    <label class="control-label" for="reportType">Report type</label>
    <select class="form-control" id="reportType" name="type" required>
      @foreach($reportTypes as $value => $label)
        <option value="{{ $value }}" @selected($reportType === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>

  <div class="form-group col-12 col-sm-6 col-md-4 col-lg-2">
    <label class="control-label" for="reportPeriod">Period</label>
    <select class="form-control" id="reportPeriod" name="period" required>
      <option value="day" @selected($period === \App\Services\DayReportService::PERIOD_DAY)>Daily</option>
      <option value="week" @selected($period === \App\Services\DayReportService::PERIOD_WEEK)>Weekly</option>
      <option value="month" @selected($period === \App\Services\DayReportService::PERIOD_MONTH)>Monthly</option>
    </select>
  </div>

  <div class="form-group col-12 col-sm-6 col-md-4 col-lg-3 js-report-date-field {{ $isMonth ? 'd-none' : '' }}">
    <label class="control-label" for="reportDate">
      {{ $isWeek ? 'Week containing' : 'Date' }}
    </label>
    <input type="date" class="form-control" id="reportDate" name="date"
      value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
    @if($isWeek)
      <small class="text-muted d-block mt-1">{{ $range['label'] }}</small>
    @endif
  </div>

  <div class="form-group col-12 col-sm-6 col-md-4 col-lg-3 js-report-month-field {{ $isMonth ? '' : 'd-none' }}">
    <label class="control-label" for="reportMonth">Month</label>
    <input type="month" class="form-control" id="reportMonth" name="month"
      value="{{ $selectedDate->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
  </div>

  <div class="form-group col-12 col-md-12 col-lg-4 mb-0 day-report-filter-actions">
    <button type="submit" class="btn btn-primary mb-2" data-loading-text="Loading report...">
      <i class="fa fa-search"></i> View report
    </button>
    <a href="{{ route('business-trends.index', request()->only(['period', 'date', 'month'])) }}"
      class="btn btn-outline-secondary mb-2 ml-1">
      <i class="fa fa-line-chart"></i> Chart trends
    </a>
  </div>
</form>

@once
  @push('scripts')
    <script>
      (function () {
        var form = document.getElementById('dayReportFilters');
        if (!form) return;

        var periodSelect = document.getElementById('reportPeriod');
        var dateField = form.querySelector('.js-report-date-field');
        var monthField = form.querySelector('.js-report-month-field');
        var dateInput = document.getElementById('reportDate');
        var monthInput = document.getElementById('reportMonth');

        function syncPeriodFields() {
          var isMonth = periodSelect.value === 'month';
          dateField.classList.toggle('d-none', isMonth);
          monthField.classList.toggle('d-none', !isMonth);
          if (dateInput) dateInput.disabled = isMonth;
          if (monthInput) monthInput.disabled = !isMonth;
        }

        periodSelect.addEventListener('change', syncPeriodFields);
        syncPeriodFields();
      })();
    </script>
  @endpush
@endonce

<form method="GET" action="{{ route('day-reports.index') }}" class="day-report-filters" id="dayReportFilters">
  <div class="day-report-filters__grid">
    <div class="day-report-filters__field">
      <label class="control-label" for="reportType">Report type</label>
      <select class="form-control" id="reportType" name="type" required>
        @foreach($reportTypes as $value => $label)
          <option value="{{ $value }}" @selected($reportType === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div class="day-report-filters__field day-report-filters__field--period">
      <label class="control-label" for="reportPeriod">Period</label>
      <select class="form-control" id="reportPeriod" name="period" required>
        <option value="day" @selected($period === \App\Services\DayReportService::PERIOD_DAY)>Daily</option>
        <option value="week" @selected($period === \App\Services\DayReportService::PERIOD_WEEK)>Weekly</option>
        <option value="month" @selected($period === \App\Services\DayReportService::PERIOD_MONTH)>Monthly</option>
        <option value="custom" @selected($period === \App\Services\DayReportService::PERIOD_CUSTOM)>Custom</option>
      </select>
    </div>

    <div class="day-report-filters__field">
      <label class="control-label" for="reportFrom">From</label>
      <input type="date" class="form-control" id="reportFrom" name="from"
        value="{{ $from->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
    </div>

    <div class="day-report-filters__field">
      <label class="control-label" for="reportTo">To</label>
      <input type="date" class="form-control" id="reportTo" name="to"
        value="{{ $to->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
    </div>

    <div class="day-report-filters__actions">
      <button type="submit" class="btn btn-primary" data-loading-text="Loading report...">
        <i class="fa fa-search"></i> View report
      </button>
    </div>
  </div>
</form>

@once
  @push('scripts')
    <script>
      (function () {
        var form = document.getElementById('dayReportFilters');
        if (!form) return;

        var periodSelect = document.getElementById('reportPeriod');
        var fromInput = document.getElementById('reportFrom');
        var toInput = document.getElementById('reportTo');
        var maxDate = fromInput.getAttribute('max');

        function parseYmd(value) {
          var parts = (value || '').split('-');
          if (parts.length !== 3) return null;
          return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        }

        function formatYmd(date) {
          var y = date.getFullYear();
          var m = String(date.getMonth() + 1).padStart(2, '0');
          var d = String(date.getDate()).padStart(2, '0');
          return y + '-' + m + '-' + d;
        }

        function clampToMax(date) {
          var max = parseYmd(maxDate);
          if (max && date > max) return max;
          return date;
        }

        function startOfWeek(date) {
          var result = new Date(date.getFullYear(), date.getMonth(), date.getDate());
          var day = result.getDay();
          var diff = day === 0 ? -6 : 1 - day;
          result.setDate(result.getDate() + diff);
          return result;
        }

        function endOfWeek(date) {
          var start = startOfWeek(date);
          var end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
          return clampToMax(end);
        }

        function applyPeriodPreset() {
          var period = periodSelect.value;
          if (period === 'custom') return;

          var today = parseYmd(maxDate) || new Date();
          var toAnchor = parseYmd(toInput.value) || today;

          if (period === 'day') {
            var day = clampToMax(today);
            fromInput.value = formatYmd(day);
            toInput.value = formatYmd(day);
            return;
          }

          if (period === 'week') {
            fromInput.value = formatYmd(startOfWeek(toAnchor));
            toInput.value = formatYmd(endOfWeek(toAnchor));
            return;
          }

          if (period === 'month') {
            fromInput.value = formatYmd(new Date(toAnchor.getFullYear(), toAnchor.getMonth(), 1));
            toInput.value = formatYmd(clampToMax(new Date(toAnchor.getFullYear(), toAnchor.getMonth() + 1, 0)));
          }
        }

        function syncToMin() {
          toInput.min = fromInput.value || '';
          if (fromInput.value && toInput.value && toInput.value < fromInput.value) {
            toInput.value = fromInput.value;
          }
        }

        periodSelect.addEventListener('change', function () {
          applyPeriodPreset();
          syncToMin();
        });

        fromInput.addEventListener('change', function () {
          if (periodSelect.value !== 'custom') {
            periodSelect.value = 'custom';
          }
          syncToMin();
        });

        toInput.addEventListener('change', function () {
          if (periodSelect.value !== 'custom') {
            periodSelect.value = 'custom';
          }
          syncToMin();
        });

        syncToMin();
      })();
    </script>
  @endpush
@endonce

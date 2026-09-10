@php($showDate = $showDate ?? false)

<div class="table-responsive detail-table-wrap day-close-staff-wrap">
  <table class="table table-hover table-bordered mb-0 detail-card-table day-close-staff-table">
    <thead>
      <tr>
        <th>Name</th>
        @if($showDate)
          <th>Date</th>
        @endif
        <th>Closed at</th>
        <th>Collected</th>
        <th>Expenses</th>
        <th>Net</th>
      </tr>
    </thead>
    <tbody>
      @foreach($staffCloses as $close)
        @php
          $isModel = is_object($close);
          $name = $isModel ? ($close->user?->name ?? '—') : ($close['user_name'] ?? '—');
          $businessDate = $isModel ? $close->business_date?->format('d M Y') : ($close['business_date'] ?? '—');
          $closedAt = $isModel ? $close->closed_at->format('H:i') : (\Carbon\Carbon::parse($close['closed_at'] ?? now())->format('H:i'));
          $collected = $isModel ? $close->summaryValue('total_collected') : ($close['total_collected'] ?? 0);
          $expensesTotal = $isModel ? $close->summaryValue('expenses_total') : ($close['expenses_total'] ?? 0);
          $net = $isModel ? $close->summaryValue('net_in_hand') : ($close['net_in_hand'] ?? 0);
        @endphp
        <tr>
          <td data-label="Name">{{ $name }}</td>
          @if($showDate)
            <td data-label="Date">{{ $businessDate }}</td>
          @endif
          <td data-label="Closed at">{{ $closedAt }}</td>
          <td data-label="Collected">{{ number_format($collected, 0) }} TZS</td>
          <td data-label="Expenses">{{ number_format($expensesTotal, 0) }} TZS</td>
          <td data-label="Net">{{ number_format($net, 0) }} TZS</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

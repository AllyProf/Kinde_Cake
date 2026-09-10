@include('day-reports.partials._report-status')

<h4 class="mb-3">Sales summary</h4>
<div class="day-close-summary-wrap mb-4">
  <table class="table table-bordered mb-0 day-close-summary-table">
    <tbody>
      <tr>
        <th style="width:45%">Sales recorded</th>
        <td>{{ number_format($summary['sales_count'] ?? 0) }} ({{ number_format($summary['sales_total'] ?? 0, 0) }} TZS)</td>
      </tr>
      <tr>
        <th>Paid sales</th>
        <td>{{ number_format($summary['paid_sales_count'] ?? 0) }}</td>
      </tr>
      <tr>
        <th>Pending / partial sales</th>
        <td>{{ number_format($summary['pending_sales_count'] ?? 0) }} ({{ number_format($summary['pending_sales_total'] ?? 0, 0) }} TZS due)</td>
      </tr>
      <tr>
        <th>Payments recorded</th>
        <td>{{ number_format($summary['payments_count'] ?? 0) }}</td>
      </tr>
    </tbody>
  </table>
</div>

<h4 class="mb-3">Sales list</h4>
@if($sales->isNotEmpty())
  <div class="table-responsive detail-table-wrap">
    <table class="table table-hover table-bordered mb-0 detail-card-table">
      <thead>
        <tr>
          <th>Sale #</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Staff</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sales as $sale)
          <tr>
            <td data-label="Sale #">
              <a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a>
            </td>
            <td data-label="Date">{{ $sale->sold_at?->format('d M Y H:i') ?? '—' }}</td>
            <td data-label="Customer">{{ $sale->customer_name ?: ($sale->customer?->name ?? 'Walk-in') }}</td>
            <td data-label="Staff">{{ $sale->user?->name ?? '—' }}</td>
            <td data-label="Total">{{ number_format($sale->total, 0) }} TZS</td>
            <td data-label="Paid">{{ number_format($sale->amount_paid, 0) }} TZS</td>
            <td data-label="Status">
              <span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <p class="text-muted mb-0">No sales recorded for this period.</p>
@endif

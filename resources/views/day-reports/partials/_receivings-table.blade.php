<div class="table-responsive detail-table-wrap">
  <table class="table table-hover table-bordered mb-0 detail-card-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Ingredient</th>
        <th>Received</th>
        <th>Added to stock</th>
        <th>Cost</th>
        <th>Supplier</th>
        <th>Recorded by</th>
      </tr>
    </thead>
    <tbody>
      @foreach($receivings as $receiving)
        <tr>
          <td data-label="Date">{{ $receiving->received_at?->format('d M Y') ?? '—' }}</td>
          <td data-label="Ingredient">{{ $receiving->ingredient?->name ?? '—' }}</td>
          <td data-label="Received">{{ $receiving->formattedEntry() }}</td>
          <td data-label="Added to stock">
            {{ number_format((float) $receiving->usage_quantity_added, 0) }}
            {{ $receiving->ingredient?->usagePackageUnit?->symbol ?? '' }}
          </td>
          <td data-label="Cost">{{ $receiving->formattedPurchaseCost() }}</td>
          <td data-label="Supplier">{{ $receiving->supplier ?: '—' }}</td>
          <td data-label="Recorded by">{{ $receiving->user?->name ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

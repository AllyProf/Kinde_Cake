@php
  $extraClass = $class ?? '';
@endphp

<div class="dashboard-stat-card {{ $extraClass }}">
  <div class="dashboard-stat-card__label">{{ $label }}</div>
  <div class="dashboard-stat-card__value">{{ $value }}</div>
</div>

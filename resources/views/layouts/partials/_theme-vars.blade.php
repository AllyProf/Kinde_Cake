@php
  $theme = app(\App\Services\AppSettingsService::class)->themeVariables();
@endphp
<style>
  :root {
    --brand: {{ $theme['brand'] }};
    --brand-dark: {{ $theme['brand-dark'] }};
    --brand-rgb: {{ $theme['brand-rgb'] }};
    --white: {{ $theme['white'] }};
    --black: {{ $theme['black'] }};
  }
</style>

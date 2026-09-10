@if($categories->isEmpty() || $packageUnits->isEmpty())
  <div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    @if($categories->isEmpty() && $packageUnits->isEmpty())
      You need categories and package units before registering items.
    @elseif($categories->isEmpty())
      You need at least one active category before registering items.
    @else
      You need at least one active package unit before registering items.
    @endif
    Go to
    <a href="{{ route('settings.index', ['tab' => 'categories']) }}">Settings</a>
    to import or add them first.
  </div>
@endif

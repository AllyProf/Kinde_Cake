<ul class="nav nav-tabs mb-3 settings-tabs">
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'business' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'business']) }}">
      <i class="fa fa-building"></i> Business
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'appearance' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'appearance']) }}">
      <i class="fa fa-paint-brush"></i> Appearance
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'categories' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'categories']) }}">
      <i class="fa fa-tags"></i> Categories
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'packages' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'packages']) }}">
      <i class="fa fa-cube"></i> Packages
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'payments' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'payments']) }}">
      <i class="fa fa-credit-card"></i> Payments
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'sms' ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => 'sms']) }}">
      <i class="fa fa-comment"></i> SMS
    </a>
  </li>
</ul>
